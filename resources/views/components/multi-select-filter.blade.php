@props(['name', 'label', 'options', 'selected' => []])

@php
$selected = collect($selected)->map(fn ($id) => (string) $id)->all();
$active = count($selected) > 0;
$summary = match (true) {
    ! $active => $label,
    count($selected) <= 2 => collect($options)->filter(fn ($text, $id) => in_array((string) $id, $selected, true))->implode(', '),
    default => count($selected).' selected',
};
@endphp

{{-- Checkbox dropdown for picking several values without Ctrl/Cmd-click.
     Submits as name[] like a native <select multiple>. --}}
<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="open = ! open"
            class="flex w-full items-center justify-between gap-2 rounded-md border px-3 py-1.5 text-left text-sm transition focus:border-brand-500 focus:ring-brand-500
                   {{ $active ? 'border-brand-300 bg-brand-50 text-brand-800 font-medium ring-1 ring-brand-200 shadow-sm' : 'border-neutral-300 bg-white text-neutral-700 hover:border-accent-400' }}"
            :aria-expanded="open.toString()">
        <span class="truncate">{{ $summary }}</span>
        <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="absolute z-30 mt-1 max-h-60 w-full min-w-[12rem] overflow-y-auto rounded-md border border-neutral-200 bg-white py-1 shadow-lg">
        @foreach ($options as $id => $text)
            <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm text-neutral-700 hover:bg-neutral-50">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $id }}" @checked(in_array((string) $id, $selected, true))
                       class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500" />
                <span>{{ $text }}</span>
            </label>
        @endforeach
    </div>
</div>
