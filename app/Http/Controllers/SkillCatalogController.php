<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Models\Position;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SkillCatalogController extends Controller
{
    use ExportsSpreadsheet;

    /** Excel header (lower-cased) => skills column. Skill Code, Description and Category are handled separately. */
    private const COLUMN_MAP = [
        'type' => 'discipline',
        'skill indicator' => 'skill_indicator',
        'sub evaluation' => 'sub_evaluation',
        'sop number' => 'sop_number',
        'plant' => 'plant',
        'bussiness unit' => 'business_unit', // sic - the source sheet's spelling
        'business unit' => 'business_unit',
        'department' => 'department',
        'area fl' => 'area_fl',
        'dept fl' => 'dept_fl',
        'class' => 'skill_class',
    ];

    private const TEMPLATE_HEADER = [
        'Skill Code', 'Skill Description', 'Category', 'Type', 'Skill Indicator', 'Sub Evaluation', 'SOP Number',
        'Plant', 'Bussiness Unit', 'Department', 'Area FL', 'Dept FL', 'Class', 'Position',
    ];

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'discipline', 'dept_fl']);

        $skills = Skill::query()
            ->with('skillCategory')
            ->withCount('positions')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($w) => $w
                ->where('code', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhere('skill_indicator', 'like', "%{$q}%")))
            ->when($filters['category'] ?? null, fn ($query, $v) => $query->where('skill_category_id', $v))
            ->when($filters['discipline'] ?? null, fn ($query, $v) => $query->where('discipline', $v))
            ->when($filters['dept_fl'] ?? null, fn ($query, $v) => $query->where('dept_fl', $v))
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        return view('skills.catalog', [
            'skills' => $skills,
            'filters' => $filters,
            'categories' => SkillCategory::orderBy('name')->get(),
            'disciplines' => Skill::whereNotNull('discipline')->distinct()->orderBy('discipline')->pluck('discipline'),
            'deptFls' => Skill::whereNotNull('dept_fl')->distinct()->orderBy('dept_fl')->pluck('dept_fl'),
            'total' => Skill::count(),
        ]);
    }

    public function template(): StreamedResponse
    {
        return $this->streamXlsx('skill-catalog-template.xlsx', self::TEMPLATE_HEADER, [
            ['M0141', 'Membaca dan memahami manual book mesin', 'PRODUCTIVITY', 'MECHANIC', 'Membaca dan memahami', 'Maintenance Basic', '', 'KA', 'TIRE CORD', 'MAINTENANCE', 'GENERAL', 'GENERAL', 'PRODUCTIVITY', ''],
        ]);
    }

    public function export(): StreamedResponse
    {
        $rows = Skill::with(['skillCategory', 'positions'])->orderBy('code')->get()->map(fn (Skill $s) => [
            $s->code, $s->description ?: $s->name, $s->skillCategory?->name, $s->discipline, $s->skill_indicator,
            $s->sub_evaluation, $s->sop_number, $s->plant, $s->business_unit, $s->department, $s->area_fl,
            $s->dept_fl, $s->skill_class, $s->positions->pluck('title')->implode('; '),
        ]);

        return $this->streamXlsx('skill-catalog-'.now()->format('Y-m-d').'.xlsx', self::TEMPLATE_HEADER, $rows);
    }

    /**
     * Upserts the master skill list from an .xlsx, keyed on Skill Code (new
     * code = new skill, existing code = updated in place). An optional
     * "Position" column (titles separated by ";") also ticks that skill on
     * those positions' default checklists; it never un-ticks anything.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx']]);

        $rows = (new XlsxReader)->load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        $header = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), array_shift($rows) ?? []);

        if (array_diff(['skill code', 'skill description'], $header)) {
            return redirect()->route('skills.catalog.index')
                ->with('import_errors', 'The file needs Skill Code and Skill Description columns - download the template to see the expected layout.');
        }

        $positions = Position::all()->groupBy(fn ($p) => mb_strtolower($p->title));
        $categories = SkillCategory::withTrashed()->get()->keyBy(fn ($c) => mb_strtolower($c->name));

        $created = 0;
        $updated = 0;
        $ticked = 0;
        $errors = [];
        $seen = [];

        DB::transaction(function () use ($rows, $header, $positions, &$categories, &$created, &$updated, &$ticked, &$errors, &$seen) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = array_combine($header, array_map(
                    fn ($v) => ($v = trim((string) $v)) === '' ? null : $v,
                    array_pad($row, count($header), null),
                ));

                $code = $data['skill code'] ?? null;
                $description = $data['skill description'] ?? null;

                if (! $code && ! $description) {
                    continue;
                }

                if (! $code || ! $description) {
                    $errors[] = "Row {$rowNumber}: Skill Code and Skill Description are both required.";

                    continue;
                }

                if (isset($seen[mb_strtolower($code)])) {
                    $errors[] = "Row {$rowNumber}: Skill Code \"{$code}\" appears twice in the file - skipped.";

                    continue;
                }
                $seen[mb_strtolower($code)] = true;

                $attributes = [
                    'name' => Str::limit($description, 255, ''),
                    'description' => $description,
                    'is_active' => true,
                ];

                foreach (self::COLUMN_MAP as $excelColumn => $column) {
                    if (array_key_exists($excelColumn, $data)) {
                        $attributes[$column] = $data[$excelColumn];
                    }
                }

                if ($categoryName = $data['category'] ?? null) {
                    $category = $categories->get(mb_strtolower($categoryName))
                        ?? tap(SkillCategory::create(['name' => $categoryName]), fn ($c) => $categories->put(mb_strtolower($categoryName), $c));
                    $category->trashed() && $category->restore();
                    $attributes['skill_category_id'] = $category->id;
                }

                $skill = Skill::withTrashed()->where('code', $code)->first() ?? new Skill(['code' => $code]);
                $isNew = ! $skill->exists;
                $skill->fill($attributes)->save();
                $skill->trashed() && $skill->restore();
                $isNew ? $created++ : $updated++;

                foreach (preg_split('/\s*[;|]\s*/', (string) ($data['position'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $title) {
                    $matches = $positions->get(mb_strtolower($title), collect());

                    if ($matches->count() !== 1) {
                        $errors[] = $matches->isEmpty()
                            ? "Row {$rowNumber}: Position \"{$title}\" not found."
                            : "Row {$rowNumber}: Position \"{$title}\" is ambiguous.";

                        continue;
                    }

                    $ticked += count($skill->positions()->syncWithoutDetaching([$matches->first()->id])['attached']);
                }
            }
        });

        $message = "{$created} skill(s) added, {$updated} updated from import.";
        $ticked > 0 && $message .= " {$ticked} position default(s) ticked.";

        $redirect = redirect()->route('skills.catalog.index')->with('status', $message);

        if ($errors !== []) {
            $shown = array_slice($errors, 0, 8);
            $suffix = count($errors) > 8 ? ' …and '.(count($errors) - 8).' more.' : '';
            $redirect->with('import_errors', count($errors).' issue(s) found: '.implode(' | ', $shown).$suffix);
        }

        return $redirect;
    }
}
