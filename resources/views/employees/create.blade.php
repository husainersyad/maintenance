<x-app-layout>
    <x-slot name="header">Add Employee</x-slot>

    <div class="max-w-4xl rounded-lg border border-neutral-200 bg-white p-6 shadow-md">
        <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
            @csrf

            @include('employees._form')

            <div class="mt-6 flex justify-end gap-2">
                <x-button variant="secondary" href="{{ route('employees.index') }}">Cancel</x-button>
                <x-button type="submit">Save Employee</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
