<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The one place the Plant Manager - hidden from every employee list and
 * count (see ExcludePlantManagerScope) - can be viewed and edited. Only
 * personal/identity details are editable here: the skill position and
 * supervisor stay as they are, since changing the skill position would put
 * them back into the employee data.
 */
class PlantManagerController extends Controller
{
    public function edit(): View
    {
        $this->authorize('managePlantManager', Employee::class);

        return view('plant-manager.edit', ['employee' => Employee::plantManager()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('managePlantManager', Employee::class);

        $employee = Employee::plantManager();
        abort_if($employee === null, 404);

        $data = $request->validate([
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($employee->id)],
            'sap_id' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'sap_id')->ignore($employee->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'date_joined' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee->update([
            ...collect($data)->except('photo')->all(),
            'updated_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }

            $employee->update(['photo_path' => $request->file('photo')->store('employee-photos', 'public')]);
        }

        return redirect()->route('plant-manager.edit')->with('status', 'Plant Manager updated.');
    }
}
