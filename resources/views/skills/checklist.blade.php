@php
    $rows = $skills->map(fn ($s) => [
        'id' => $s->id,
        'code' => $s->code,
        'text' => $s->description ?: $s->name,
        'category' => $s->skillCategory?->name,
        'discipline' => $s->discipline,
        'indicator' => $s->skill_indicator,
        'dept_fl' => $s->dept_fl,
    ]);
@endphp
<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>

    <div class="space-y-4"
         x-data="{
            skills: @js($rows),
            checked: @js(array_map('intval', $checked)),
            defaults: @js($defaults === null ? null : array_map('intval', $defaults)),
            q: '', category: '', discipline: '', dept_fl: '', onlyChecked: false,
            get visible() {
                const q = this.q.toLowerCase();
                return this.skills.filter(s =>
                    (!q || (s.code + ' ' + s.text + ' ' + (s.indicator ?? '')).toLowerCase().includes(q))
                    && (!this.category || s.category === this.category)
                    && (!this.discipline || s.discipline === this.discipline)
                    && (!this.dept_fl || s.dept_fl === this.dept_fl)
                    && (!this.onlyChecked || this.checked.includes(s.id)));
            },
            opts(key) { return [...new Set(this.skills.map(s => s[key]).filter(Boolean))].sort(); },
            toggle(id) { this.checked.includes(id) ? this.checked = this.checked.filter(i => i !== id) : this.checked.push(id); },
            setVisible(on) { const ids = this.visible.map(s => s.id); this.checked = on ? [...new Set([...this.checked, ...ids])] : this.checked.filter(i => !ids.includes(i)); },
            badge(id) {
                if (this.defaults === null) return '';
                const c = this.checked.includes(id), d = this.defaults.includes(id);
                return c && !d ? 'added' : (!c && d ? 'removed' : '');
            },
         }">
        <a href="{{ $back['url'] }}" class="text-sm text-neutral-600 hover:underline">&larr; {{ $back['label'] }}</a>
        <p class="text-sm text-neutral-600">{{ $intro }}</p>

        <form method="POST" action="{{ $action }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-end gap-2">
                <input type="search" x-model="q" placeholder="Search code, description, indicator" class="w-64 rounded-md border-neutral-300 text-sm">
                <select x-model="category" class="rounded-md border-neutral-300 text-sm"><option value="">All categories</option><template x-for="o in opts('category')" :key="o"><option :value="o" x-text="o"></option></template></select>
                <select x-model="discipline" class="rounded-md border-neutral-300 text-sm"><option value="">All types</option><template x-for="o in opts('discipline')" :key="o"><option :value="o" x-text="o"></option></template></select>
                <select x-model="dept_fl" class="rounded-md border-neutral-300 text-sm"><option value="">All Dept FL</option><template x-for="o in opts('dept_fl')" :key="o"><option :value="o" x-text="o"></option></template></select>
                <label class="flex items-center gap-1.5 text-sm text-neutral-600"><input type="checkbox" x-model="onlyChecked" class="rounded border-neutral-300"> Ticked only</label>
                <button type="button" @click="setVisible(true)" class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50">Tick all shown</button>
                <button type="button" @click="setVisible(false)" class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50">Untick all shown</button>
                <span class="text-xs text-neutral-500"><span x-text="checked.length"></span> ticked · <span x-text="visible.length"></span> shown</span>
            </div>

            <template x-for="id in checked" :key="id"><input type="hidden" name="skills[]" :value="id"></template>

            <div class="max-h-[65vh] overflow-auto rounded-lg border border-neutral-200 bg-white shadow-md">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="sticky top-0 border-b-2 border-brand-500 bg-brand-50">
                        <tr>
                            <th class="w-10 px-3 py-2"></th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Code</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Skill</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Category</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">Dept FL</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <template x-for="s in visible" :key="s.id">
                            <tr class="cursor-pointer hover:bg-neutral-50" @click="toggle(s.id)">
                                <td class="px-3 py-2"><input type="checkbox" class="rounded border-neutral-300" :checked="checked.includes(s.id)" @click.stop="toggle(s.id)"></td>
                                <td class="whitespace-nowrap px-3 py-2 font-medium text-neutral-900" x-text="s.code"></td>
                                <td class="px-3 py-2 text-neutral-800" x-text="s.text"></td>
                                <td class="px-3 py-2 text-neutral-600" x-text="s.category ?? '—'"></td>
                                <td class="px-3 py-2 text-neutral-600" x-text="s.discipline ?? '—'"></td>
                                <td class="px-3 py-2 text-neutral-600" x-text="s.dept_fl ?? '—'"></td>
                                <td class="px-3 py-2 text-xs">
                                    <span x-show="badge(s.id) === 'added'" class="rounded-full bg-success-50 px-2 py-0.5 text-success-800">added</span>
                                    <span x-show="badge(s.id) === 'removed'" class="rounded-full bg-warning-50 px-2 py-0.5 text-warning-800">removed from default</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @if (auth()->user()->canEditMenu(\App\Enums\MenuKey::SkillsCompetencies))
                <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700">Save checklist</button>
            @else
                <p class="text-xs text-neutral-500">You have read-only access to this menu.</p>
            @endif
        </form>
    </div>
</x-app-layout>
