<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\SkillPosition;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantManagerTest extends TestCase
{
    use RefreshDatabase;

    private Employee $plantManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->plantManager = Employee::factory()->create([
            'employee_number' => '1',
            'full_name' => 'Plant Boss',
            'supervisor_id' => null,
            'skill_position_id' => SkillPosition::factory()->create(['name' => SkillPosition::PLANT_MANAGER])->id,
        ]);
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_plant_manager_is_excluded_from_every_employee_query_and_total(): void
    {
        Employee::factory()->count(3)->create();
        // An employee with no skill position at all must still be counted.
        Employee::factory()->create(['skill_position_id' => null]);

        $this->assertSame(4, Employee::count());
        $this->assertNull(Employee::find($this->plantManager->id));
        $this->assertSame($this->plantManager->id, Employee::plantManager()->id);
    }

    public function test_plant_manager_is_not_listed_or_counted_on_the_employees_page(): void
    {
        Employee::factory()->count(2)->create();
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->get(route('employees.index'))
            ->assertOk()
            ->assertDontSee('Plant Boss')
            ->assertViewHas('employees', fn ($employees) => $employees->total() === 2);
    }

    public function test_plant_manager_profile_is_not_reachable_through_the_employee_routes(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->get(route('employees.show', $this->plantManager->id))->assertNotFound();
        $this->actingAs($admin)->get(route('employees.edit', $this->plantManager->id))->assertNotFound();
    }

    public function test_administrator_can_edit_the_plant_manager_from_the_dedicated_menu(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->get(route('plant-manager.edit'))
            ->assertOk()
            ->assertSee('Plant Boss');

        $this->actingAs($admin)->put(route('plant-manager.update'), [
            'employee_number' => '1',
            'full_name' => 'New Plant Boss',
            'email' => 'boss@example.test',
        ])->assertRedirect(route('plant-manager.edit'));

        $fresh = Employee::plantManager();
        $this->assertSame('New Plant Boss', $fresh->full_name);
        $this->assertSame('boss@example.test', $fresh->email);
        // Still the Plant Manager - the edit form can't move them out of it.
        $this->assertSame($this->plantManager->skill_position_id, $fresh->skill_position_id);
        $this->assertNull(Employee::find($this->plantManager->id));
    }

    public function test_editing_the_plant_manager_rejects_a_number_used_by_another_employee(): void
    {
        $other = Employee::factory()->create(['employee_number' => '2']);
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->put(route('plant-manager.update'), [
            'employee_number' => $other->employee_number,
            'full_name' => 'Plant Boss',
        ])->assertSessionHasErrors('employee_number');
    }

    public function test_only_users_who_manage_all_employees_can_open_the_plant_manager_menu(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MaintenanceStaff))->get(route('plant-manager.edit'))->assertForbidden();
        $this->actingAs($this->userWithRole(RoleName::MaintenanceStaff))
            ->put(route('plant-manager.update'), ['employee_number' => '1', 'full_name' => 'x'])->assertForbidden();
        $this->actingAs($this->userWithRole(RoleName::Guest))->get(route('plant-manager.edit'))->assertForbidden();
    }

    public function test_plant_manager_skill_position_cannot_be_assigned_to_a_regular_employee(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->post(route('employees.store'), [
            'employee_number' => '99',
            'full_name' => 'Sneaky',
            'skill_position_id' => $this->plantManager->skill_position_id,
        ])->assertSessionHasErrors('skill_position_id');

        $this->assertSame(0, Employee::where('employee_number', '99')->count());
    }

    public function test_plant_manager_is_missing_from_the_skill_position_dropdown(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator);

        $this->actingAs($admin)->get(route('employees.create'))
            ->assertOk()
            ->assertViewHas('skillPositions', fn ($positions) => $positions->doesntContain('name', SkillPosition::PLANT_MANAGER));
    }
}
