<x-app-layout>
    <x-slot name="header">Organization Chart</x-slot>

    <style>
        /* Classic pure-CSS org-chart tree: each <li> is a node plus its
           subtree, connected to its parent/siblings with border lines
           instead of an image or JS charting library. */
        .org-chart-tree, .org-chart-tree ul {
            list-style: none;
            margin: 0;
            padding-top: 24px;
            position: relative;
        }
        .org-chart-tree { padding-top: 0; display: inline-flex; }
        .org-chart-tree ul { display: flex; }
        .org-chart-tree li {
            position: relative;
            padding: 24px 12px 0 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .org-chart-tree li::before,
        .org-chart-tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 2px solid #999087;
            width: 50%;
            height: 24px;
        }
        .org-chart-tree li::after { right: auto; left: 50%; border-left: 2px solid #999087; }
        .org-chart-tree li:only-child::before, .org-chart-tree li:only-child::after { display: none; }
        .org-chart-tree li:only-child { padding-top: 0; }
        .org-chart-tree li:first-child::before { border: 0 none; }
        .org-chart-tree li:last-child::after { border: 0 none; }
        .org-chart-tree li:last-child::before { border-right: 2px solid #999087; border-radius: 0 6px 0 0; }
        .org-chart-tree li:first-child::after { border-radius: 6px 0 0 0; }
        .org-chart-tree > li { padding-top: 0; }
        .org-chart-tree > li::before, .org-chart-tree > li::after { display: none; }
        /* An all-Blue-Collar group: two columns under a single vertical line
           instead of one long row of siblings. */
        .org-chart-tree ul.org-chart-stack {
            display: grid;
            grid-template-columns: repeat(2, max-content);
            justify-content: center;
            padding-top: 16px;
        }
        .org-chart-tree ul.org-chart-stack > li { padding-top: 12px; }
        .org-chart-tree ul.org-chart-stack > li::before,
        .org-chart-tree ul.org-chart-stack > li::after { display: none; }
        .org-chart-tree ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 2px solid #999087;
            width: 0;
            height: 24px;
        }
        /* Warm-gray canvas with a faint dot grid so white (WCM) cards stand
           out instead of blending into a white background. */
        .org-chart-canvas {
            background-color: #EBE9E5;
            background-image: radial-gradient(#D7D2CB 1.2px, transparent 1.2px);
            background-size: 18px 18px;
        }
    </style>

    @php
        $activeFieldClass = 'border-brand-300 bg-brand-50 text-brand-800 font-medium ring-1 ring-brand-200 shadow-sm';
        $defaultFieldClass = 'border-neutral-300 hover:border-accent-400';
        $filterActiveCount = collect($filters)->filter(fn ($value) => filled($value))->count();
    @endphp

    <div class="space-y-4">
        <x-filter-panel :active-count="$filterActiveCount">
            <form method="GET" action="{{ route('organization-chart.index') }}" class="grid grid-cols-1 gap-2.5 rounded-lg border border-neutral-200 bg-white p-3 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or NIK..."
                       class="rounded-md text-sm py-1.5 transition focus:border-brand-500 focus:ring-brand-500 sm:col-span-2 {{ filled($filters['search'] ?? null) ? $activeFieldClass : $defaultFieldClass }}" />

                <input type="hidden" name="applied" value="1" />

                <x-multi-select-filter name="business_unit_id" label="All Business Units"
                                       :options="$businessUnits->pluck('name', 'id')" :selected="$filters['business_unit_id']" />

                <x-multi-select-filter name="department_id" label="All Departments"
                                       :options="$departments->pluck('name', 'id')" :selected="$filters['department_id']" />

                <x-multi-select-filter name="employment_type_id" label="All Employment Types"
                                       :options="$employmentTypes->pluck('name', 'id')" :selected="$filters['employment_type_id']" />

                <div class="flex gap-2">
                    <x-button size="sm" type="submit">Filter</x-button>
                    <x-button variant="secondary" size="sm" href="{{ route('organization-chart.index') }}">Reset</x-button>
                </div>
            </form>
        </x-filter-panel>

        <div class="sticky top-0 z-20 flex flex-wrap items-center gap-4 rounded-lg border border-neutral-200 bg-white p-4 text-sm shadow-md">
            <span class="font-medium text-neutral-700">Legend:</span>
            <span class="inline-flex items-center gap-2">
                <span class="h-4 w-4 rounded border border-accent-700 bg-accent-600"></span>
                Blue Collar (BC)
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-700">{{ $workforceCounts->get('BC', 0) }}</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="h-4 w-4 rounded border border-neutral-400 bg-white"></span>
                White Collar Management (WCM)
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-700">{{ $workforceCounts->get('WCM', 0) }}</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="h-4 w-4 rounded border border-pink-700 bg-pink-500"></span>
                Internship (Non BC/WCM)
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-700">{{ $workforceCounts->get('INTERN', 0) }}</span>
            </span>
            <span class="inline-flex items-center gap-2 text-neutral-600">
                <span class="font-semibold">M</span> Mechanic
                <span class="font-semibold">E</span> Electric
            </span>
            @if ($filtered)
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    Showing {{ $matchCount }} {{ Str::plural('match', $matchCount) }} and their reporting line to the top
                </span>
            @endif
            <span class="ml-auto inline-flex gap-2">
                <x-button variant="secondary" size="xs" type="button" @click="$dispatch('org-expand-all')">Expand all</x-button>
                <x-button variant="secondary" size="xs" type="button" @click="$dispatch('org-collapse-all')">Collapse all</x-button>
            </span>
        </div>

        @if ($roots->isEmpty())
            <div class="rounded-lg border border-dashed border-neutral-300 bg-white p-10 text-center text-sm text-neutral-500">
                @if ($filtered)
                    No employees match your search or filters.
                @else
                    No employees to show yet.
                @endif
            </div>
        @else
            <div class="org-chart-canvas overflow-x-auto rounded-lg border border-neutral-300 p-8 shadow-sm">
                <ul class="org-chart-tree">
                    @foreach ($roots as $root)
                        @include('organization-chart._node', ['employee' => $root, 'childrenByParent' => $childrenByParent, 'highlightIds' => $highlightIds, 'forceExpand' => $searching, 'teamTrades' => $teamTrades])
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
