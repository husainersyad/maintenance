<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Models\Employee;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A supervisor's review of one employee's skill checklist, starting from the
 * position default and storing only what was added or removed.
 */
class EmployeeSkillChecklistController extends Controller
{
    public function edit(Request $request, Employee $employee): View
    {
        $this->authorizeReview($request, $employee);

        return view('skills.checklist', [
            'title' => 'Skill Checklist — '.$employee->full_name,
            'intro' => ($employee->position
                ? "Starts from the default skills of {$employee->position->title}. Tick to add a skill, untick to remove one - only this employee is affected."
                : 'This employee has no position yet, so there is no default - tick the skills that apply.'),
            'back' => ['url' => route('employees.show', $employee), 'label' => 'Back to Employee'],
            'action' => route('employees.skill-checklist.update', $employee),
            'skills' => Skill::with('skillCategory')->where('is_active', true)->orderBy('code')->get(),
            'checked' => $employee->checklistSkillIds(),
            'defaults' => $employee->position ? $employee->position->skills()->pluck('skills.id')->all() : [],
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeReview($request, $employee);

        $data = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $checked = collect($data['skills'] ?? []);
        $defaults = collect($employee->position ? $employee->position->skills()->pluck('skills.id') : []);

        $added = $checked->diff($defaults)->map(fn ($id) => ['skill_id' => $id, 'action' => 'add']);
        $removed = $defaults->diff($checked)->map(fn ($id) => ['skill_id' => $id, 'action' => 'remove']);

        $employee->skillOverrides()->delete();

        foreach ($added->concat($removed) as $override) {
            $employee->skillOverrides()->create($override + ['updated_by' => $request->user()->id]);
        }

        return redirect()->route('employees.show', $employee)->with('status', 'Skill checklist updated.');
    }

    private function authorizeReview(Request $request, Employee $employee): void
    {
        $this->authorize('view', $employee);
        abort_unless($request->user()->hasPermissionTo(PermissionName::AssessCompetencies->value), 403);
    }
}
