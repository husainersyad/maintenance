<x-app-layout>
    <x-slot name="header">Skill Database</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('skills.landing') }}" class="text-sm text-neutral-600 hover:underline">&larr; Back to Skills &amp; Competencies</a>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('skills.catalog.template') }}" class="rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50">Download Template</a>
                <a href="{{ route('skills.catalog.export') }}" class="rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50">Export XLSX</a>

                @if (auth()->user()->canEditMenu(\App\Enums\MenuKey::SkillsCompetencies))
                    <form method="POST" action="{{ route('skills.catalog.import') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-brand-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700">
                            Import Excel
                            <input type="file" name="file" accept=".xlsx" class="hidden" onchange="this.form.requestSubmit()">
                        </label>
                    </form>
                @endif
            </div>
        </div>

        <p class="text-xs text-neutral-500">Import is keyed on <strong>Skill Code</strong>: new codes are added, existing codes are updated. An optional <strong>Position</strong> column (separate several with <code>;</code>) ticks the skill as a default for those positions.</p>

        @if (session('status'))
            <div class="rounded-md bg-success-50 px-4 py-3 text-sm text-success-800">{{ session('status') }}</div>
        @endif
        @if (session('import_errors'))
            <div class="rounded-md border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">{{ session('import_errors') }}</div>
        @endif
        @error('file')
            <div class="rounded-md border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">{{ $message }}</div>
        @enderror

        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search code, description, indicator" class="w-64 rounded-md border-neutral-300 text-sm">
            <select name="category" class="rounded-md border-neutral-300 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="discipline" class="rounded-md border-neutral-300 text-sm">
                <option value="">All types</option>
                @foreach ($disciplines as $d)
                    <option value="{{ $d }}" @selected(($filters['discipline'] ?? '') === $d)>{{ $d }}</option>
                @endforeach
            </select>
            <select name="dept_fl" class="rounded-md border-neutral-300 text-sm">
                <option value="">All Dept FL</option>
                @foreach ($deptFls as $d)
                    <option value="{{ $d }}" @selected(($filters['dept_fl'] ?? '') === $d)>{{ $d }}</option>
                @endforeach
            </select>
            <button class="rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">Filter</button>
            <span class="text-xs text-neutral-500">{{ $skills->total() }} of {{ $total }} skill(s)</span>
        </form>

        <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-md">
            <table class="min-w-full divide-y divide-neutral-200 text-sm">
                <thead class="border-b-2 border-brand-500 bg-brand-50">
                    <tr>
                        @foreach (['Code', 'Skill Description', 'Category', 'Type', 'Skill Indicator', 'Sub Evaluation', 'SOP Number', 'Area FL', 'Dept FL', 'Class', 'Positions'] as $heading)
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($skills as $skill)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-neutral-900">{{ $skill->code }}</td>
                            <td class="px-3 py-2 text-neutral-800">{{ $skill->description ?: $skill->name }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->skillCategory?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->discipline ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->skill_indicator ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->sub_evaluation ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-neutral-600">{{ $skill->sop_number ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->area_fl ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->dept_fl ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->skill_class ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $skill->positions_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-4 py-10 text-center text-neutral-500">No skills yet - import your Excel file to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $skills->links() }}
    </div>
</x-app-layout>
