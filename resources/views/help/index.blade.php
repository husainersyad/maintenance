<x-app-layout>
    <x-slot name="header">Help</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <nav aria-label="Help contents" class="shrink-0 rounded-lg border border-neutral-200 bg-white p-4 shadow-md lg:sticky lg:top-4 lg:w-64">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">Daftar Isi</p>
            <ul class="space-y-1 text-sm">
                @foreach ($toc as $id => $title)
                    <li><a href="#{{ $id }}" class="block rounded px-2 py-1 text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900">{{ $title }}</a></li>
                @endforeach
            </ul>
        </nav>

        <article class="prose prose-neutral min-w-0 max-w-3xl flex-1 rounded-lg border border-neutral-200 bg-white p-6 shadow-md prose-headings:scroll-mt-20 prose-table:text-sm [&_.help-anchor]:ml-2 [&_.help-anchor]:text-neutral-300 [&_.help-anchor]:no-underline">
            {!! $html !!}
        </article>
    </div>
</x-app-layout>
