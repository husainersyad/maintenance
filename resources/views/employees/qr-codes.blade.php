<x-app-layout>
    <x-slot name="header">Generate QR Codes</x-slot>

    <div class="space-y-4 print:space-y-2">
        <div class="sticky top-0 z-10 -mx-4 space-y-4 bg-neutral-100 px-4 pb-4 pt-1 print:static print:mx-0 print:space-y-2 print:bg-transparent print:px-0 print:pb-0 print:pt-0">
            <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
                <p class="text-sm text-neutral-500">Certificate verification QR code for each employee - scan or print for a badge. Links to the same public page as the one on an employee's profile.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('employees.index') }}" class="rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50">&larr; Back</a>
                    <button type="button" onclick="window.print()" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700">Print This Page</button>
                </div>
            </div>

            <form method="GET" class="flex gap-2 print:hidden">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search NIK, name, department..." class="w-full max-w-sm rounded-md border-neutral-300 text-sm">
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700">Search</button>
                @if (filled($filters['search'] ?? null))
                    <a href="{{ route('employees.qr-codes') }}" class="rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50">Reset</a>
                @endif
            </form>

            <div id="qr-base-url-panel" data-base-url="{{ $qrBaseUrl }}" class="rounded-lg border border-neutral-200 bg-white p-4 shadow-md print:hidden">
                @if (session('status'))
                    <div class="mb-3 rounded-md bg-success-50 px-4 py-3 text-sm text-success-800">{{ session('status') }}</div>
                @endif

                <label for="qr-base-url" class="block text-sm font-medium text-neutral-700">Base URL used in the QR codes</label>
                @if ($canEditQrBaseUrl)
                    <p class="mt-1 text-xs text-neutral-500">
                        Site-wide setting, only the Administrator can change it. Leave empty to use this site's current address ({{ url('/') }}).
                    </p>
                    <form method="POST" action="{{ route('employees.qr-codes.base-url') }}" class="mt-2 flex flex-col gap-2 sm:flex-row">
                        @csrf
                        @method('PUT')
                        <input type="text" id="qr-base-url" name="qr_base_url" value="{{ old('qr_base_url', \App\Models\Setting::get('qr_base_url')) }}" placeholder="https://your-domain.example.com"
                               class="w-full min-w-0 flex-1 rounded-md border-neutral-300 text-sm">
                        <button type="submit" class="shrink-0 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700">Save</button>
                    </form>
                    <x-input-error :messages="$errors->get('qr_base_url')" class="mt-1" />
                @else
                    <p class="mt-1 text-xs text-neutral-500">Only the Administrator can change this.</p>
                    <input type="text" id="qr-base-url" value="{{ $qrBaseUrl }}" readonly disabled
                           class="mt-2 w-full rounded-md border-neutral-300 bg-neutral-100 text-sm text-neutral-500">
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 print:grid-cols-3">
            @forelse ($employees as $employee)
                @php $path = route('verify.employee', $employee, false); @endphp
                <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-md text-center print:break-inside-avoid">
                    <h3 class="truncate text-sm font-semibold text-neutral-900">{{ $employee->full_name }}</h3>
                    <p class="text-xs text-neutral-500">NIK: {{ $employee->employee_number }}</p>
                    <div class="my-3 flex justify-center">
                        <canvas data-qrcode data-qr-path="{{ $path }}" class="h-[160px] w-[160px] rounded-md border border-neutral-200 bg-white"></canvas>
                    </div>
                    <p class="break-all text-[10px] text-neutral-400" data-qr-url-text></p>
                    <button type="button" data-download-qr data-filename="{{ $employee->employee_number }}"
                            data-name="{{ $employee->full_name }}" data-nik="{{ $employee->employee_number }}"
                            class="mt-2 w-full rounded-md border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 print:hidden">
                        Download PNG
                    </button>
                </div>
            @empty
                <div class="col-span-full rounded-lg border border-neutral-200 bg-white p-10 text-center text-neutral-500 shadow-md">No employees found.</div>
            @endforelse
        </div>

        <div class="print:hidden">
            {{ $employees->links() }}
        </div>
    </div>
</x-app-layout>
