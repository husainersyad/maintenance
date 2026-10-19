<x-app-layout>
    <x-slot name="header">Edit Employee</x-slot>

    <div class="max-w-4xl rounded-lg border border-neutral-200 bg-white p-6 shadow-md">
        <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('employees._form')

            @if (request('from'))
                <input type="hidden" name="from" value="{{ request('from') }}">
            @endif

            <div class="mt-6 flex justify-end gap-2">
                <x-button variant="secondary" href="{{ route('employees.show', array_filter(['employee' => $employee, 'from' => request('from')])) }}">Cancel</x-button>
                <x-button type="submit">Save Changes</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
