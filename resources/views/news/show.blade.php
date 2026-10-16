<x-app-layout>
    <x-slot name="header">News</x-slot>

    <div class="mx-auto max-w-3xl space-y-4">
        <a href="{{ route('dashboard') }}" class="text-sm text-neutral-500 hover:underline">&larr; Back to Dashboard</a>

        @if (session('status'))
            <div class="rounded-md bg-success-50 px-4 py-3 text-sm text-success-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-md">
            @if ($news->image_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($news->image_path) }}" alt="{{ $news->title }}" class="max-h-96 w-full object-cover">
            @endif

            <div class="p-6">
                <h1 class="text-xl font-semibold text-neutral-900">{{ $news->title }}</h1>
                <p class="mt-1 text-xs text-neutral-400">
                    {{ $news->created_at->format('d M Y') }}
                    @if ($news->createdBy)
                        &middot; {{ $news->createdBy->name }}
                    @endif
                </p>
                <div class="prose prose-sm mt-4 max-w-none leading-relaxed text-neutral-700">{!! $news->body !!}</div>
            </div>
        </div>

        @if ($ownAcknowledgement)
            <div class="rounded-lg border p-4 shadow-md {{ $ownAcknowledgement->acknowledged_at ? 'border-success-200 bg-success-50' : 'border-warning-300 bg-warning-50' }}">
                @if ($ownAcknowledgement->acknowledged_at)
                    <p class="text-sm font-medium text-success-800">
                        You confirmed that you have read and understood this announcement on
                        {{ $ownAcknowledgement->acknowledged_at->format('d M Y, H:i:s') }}.
                    </p>
                @else
                    <form method="POST" action="{{ route('news.acknowledge', $news) }}" x-data="{ agreed: false }" class="space-y-3">
                        @csrf
                        <p class="text-sm font-semibold text-warning-800">This announcement requires your confirmation.</p>
                        <label class="flex items-start gap-2 text-sm text-neutral-700">
                            <input type="checkbox" x-model="agreed" class="mt-0.5 rounded border-neutral-300">
                            <span>I have read and understood this announcement.</span>
                        </label>
                        <button type="submit" :disabled="!agreed" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">Confirm</button>
                    </form>
                @endif
            </div>
        @endif

        @if ($news->requires_acknowledgement && auth()->user()->hasPermissionTo(\App\Enums\PermissionName::ManageNews->value))
            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-md">
                <div class="border-b border-neutral-200 px-4 py-3 text-sm font-semibold text-neutral-900">
                    Confirmations ({{ $acknowledgements->whereNotNull('acknowledged_at')->count() }} of {{ $acknowledgements->count() }})
                </div>
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-brand-50 text-left text-xs font-semibold uppercase tracking-wide text-brand-700">
                        <tr>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Confirmed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($acknowledgements as $row)
                            <tr>
                                <td class="px-4 py-2 text-neutral-900">{{ $row->user->name }} <span class="text-xs text-neutral-400">{{ $row->user->email }}</span></td>
                                <td class="px-4 py-2">
                                    @if ($row->acknowledged_at)
                                        <span class="inline-flex rounded-full bg-success-100 px-2 py-1 text-xs font-medium text-success-800">Confirmed</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-warning-100 px-2 py-1 text-xs font-medium text-warning-800">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-neutral-600">{{ $row->acknowledged_at?->format('d M Y, H:i:s') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
