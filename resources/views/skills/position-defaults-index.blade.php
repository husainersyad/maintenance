<x-app-layout>
    <x-slot name="header">Default Skills per Position</x-slot>

    <div class="space-y-4">
        <a href="{{ route('skills.landing') }}" class="text-sm text-neutral-600 hover:underline">&larr; Back to Skills &amp; Competencies</a>

        @if (session('status'))
            <div class="rounded-md bg-success-50 px-4 py-3 text-sm text-success-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-md">
            <table class="min-w-full divide-y divide-neutral-200 text-sm">
                <thead class="border-b-2 border-brand-500 bg-brand-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Position</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Department</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Default Skills</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($positions as $position)
                        <tr>
                            <td class="px-3 py-2 font-medium text-neutral-900">{{ $position->title }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $position->department?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-neutral-600">{{ $position->skills_count }} / {{ $totalSkills }}</td>
                            <td class="px-3 py-2 text-right"><a href="{{ route('skills.position-defaults.edit', $position) }}" class="text-neutral-600 hover:underline">Tick skills</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-neutral-500">No active positions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $positions->links() }}
    </div>
</x-app-layout>
