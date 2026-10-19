@props(['href' => '#', 'active' => false, 'icon' => null, 'top' => false])

{{-- Sidebar link. Must live inside the `sidebar` Alpine scope (provides `rail`
     and the tooltip helpers). data-* attributes feed the Ctrl+K menu search.

     `top` = a standalone first-level entry (e.g. Dashboard), styled like a group
     header. Otherwise this is a sub-item: smaller, lighter, icon-less and
     indented under its group (the icon only shows in the collapsed rail). --}}
@php
$label = trim(html_entity_decode(strip_tags((string) $slot)));

$base = 'group relative flex items-center rounded-md transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 ';

$accentBar = $top
    ? 'before:absolute before:inset-y-1.5 before:left-0 before:w-0.5 before:rounded-full before:bg-brand-500'
    : 'before:absolute before:inset-y-1 before:-left-[9px] before:w-0.5 before:rounded-full before:bg-brand-500';

$classes = $base
    .($top ? 'gap-2.5 py-2 text-sm font-semibold ' : 'gap-2.5 py-1.5 text-[13px] font-normal ')
    .($active
        ? 'bg-brand-500/15 text-white '.$accentBar.($top ? '' : ' [&_span]:font-medium')
        : ($top ? 'text-neutral-200 hover:bg-neutral-800 hover:text-white' : 'text-neutral-400 hover:bg-neutral-800 hover:text-white'));

$iconClasses = 'shrink-0 transition-colors duration-150 '
    .($active ? 'text-brand-400' : 'text-neutral-500 group-hover:text-neutral-200');

$padding = $top ? 'px-3' : 'pl-2.5 pr-2';
@endphp

<a href="{{ $href }}"
   data-nav-item
   data-label="{{ $label }}"
   data-icon="{{ $icon }}"
   @if ($active) aria-current="page" @endif
   @mouseenter="showTip($el)"
   @mouseleave="hideTip()"
   @focus="showTip($el)"
   @blur="hideTip()"
   class="{{ $classes }}"
   :class="rail ? 'justify-center px-0' : '{{ $padding }}'">
    @if ($icon)
        <svg xmlns="http://www.w3.org/2000/svg"
             @if ($top) class="{{ $iconClasses }} h-5 w-5" @else class="{{ $iconClasses }}" :class="rail ? 'h-5 w-5' : 'hidden'" @endif
             fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
    @endif
    {{-- Stays in the DOM as sr-only in rail mode so the link keeps its accessible name. --}}
    <span :class="rail ? 'sr-only' : 'min-w-0 flex-1 truncate'">{{ $slot }}</span>
</a>
