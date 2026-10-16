<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SkillCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(RoleName $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role->value));
    }

    private function xlsx(array $rows): UploadedFile
    {
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'skl').'.xlsx';
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'skills.xlsx', null, null, true);
    }

    private const HEADER = ['Skill Code', 'Skill Description', 'Category', 'Type', 'Skill Indicator', 'Sub Evaluation', 'SOP Number', 'Plant', 'Bussiness Unit', 'Department', 'Area FL', 'Dept FL', 'Class'];

    public function test_import_adds_then_updates_skills_by_code(): void
    {
        $manager = $this->user(RoleName::MaintenanceManager);

        $this->actingAs($manager)->post(route('skills.catalog.import'), ['file' => $this->xlsx([
            self::HEADER,
            ['M0141', 'Membaca manual book', 'PRODUCTIVITY', 'MECHANIC', 'Membaca', 'Maintenance Basic', null, 'KA', 'TIRE CORD', 'MAINTENANCE', 'GENERAL', 'GENERAL', 'PRODUCTIVITY'],
            ['M0028', 'Be Care', 'SAFETY', 'OTHER', 'Safety Knowledge', 'Safety Knowledge', null, 'ALL', 'ALL', 'MAINTENANCE', 'GENERAL', 'GENERAL', 'SAFETY'],
        ])])->assertRedirect(route('skills.catalog.index'));

        $this->assertSame(2, Skill::count());
        $skill = Skill::where('code', 'M0141')->first();
        $this->assertSame('MECHANIC', $skill->discipline);
        $this->assertSame('PRODUCTIVITY', $skill->skillCategory->name);
        $this->assertSame('KA', $skill->plant);

        $this->actingAs($manager)->post(route('skills.catalog.import'), ['file' => $this->xlsx([
            self::HEADER,
            ['M0141', 'Membaca manual book (revisi)', 'PRODUCTIVITY', 'MECHANIC', 'Membaca', 'Maintenance Basic', 'SI-M-1', 'KA', 'TIRE CORD', 'MAINTENANCE', 'GENERAL', 'GENERAL', 'PRODUCTIVITY'],
        ])]);

        $this->assertSame(2, Skill::count());
        $this->assertSame('Membaca manual book (revisi)', $skill->fresh()->name);
        $this->assertSame('SI-M-1', $skill->fresh()->sop_number);
    }

    public function test_import_with_position_column_ticks_position_defaults(): void
    {
        $position = Position::factory()->create(['title' => 'Mechanic Technician']);

        $this->actingAs($this->user(RoleName::MaintenanceManager))->post(route('skills.catalog.import'), ['file' => $this->xlsx([
            [...self::HEADER, 'Position'],
            ['M0001', 'Balancing', 'PRODUCTIVITY', 'MECHANIC', null, null, null, null, null, null, null, null, null, 'Mechanic Technician; Ghost'],
        ])])->assertSessionHas('import_errors');

        $this->assertTrue($position->skills()->where('code', 'M0001')->exists());
    }

    public function test_import_rejects_file_without_required_columns(): void
    {
        $this->actingAs($this->user(RoleName::MaintenanceManager))->post(route('skills.catalog.import'), ['file' => $this->xlsx([
            ['Foo'], ['bar'],
        ])])->assertSessionHas('import_errors');

        $this->assertSame(0, Skill::count());
    }

    public function test_supervisor_cannot_open_catalog_or_import(): void
    {
        $supervisor = $this->user(RoleName::MaintenanceSupervisor);

        $this->actingAs($supervisor)->get(route('skills.catalog.index'))->assertForbidden();
        $this->actingAs($supervisor)->post(route('skills.catalog.import'), [])->assertForbidden();
    }

    public function test_position_defaults_can_be_saved(): void
    {
        $position = Position::factory()->create();
        $skills = Skill::factory()->count(3)->create();

        $this->actingAs($this->user(RoleName::MaintenanceManager))
            ->put(route('skills.position-defaults.update', $position), ['skills' => [$skills[0]->id, $skills[1]->id]])
            ->assertRedirect(route('skills.position-defaults.index'));

        $this->assertEqualsCanonicalizing([$skills[0]->id, $skills[1]->id], $position->skills()->pluck('skills.id')->all());
    }

    public function test_supervisor_review_stores_only_differences_from_position_default(): void
    {
        $supervisor = $this->user(RoleName::MaintenanceSupervisor);
        $position = Position::factory()->create();
        [$a, $b, $c] = Skill::factory()->count(3)->create();
        $position->skills()->sync([$a->id, $b->id]);

        $employee = Employee::factory()->create(['position_id' => $position->id, 'supervisor_id' => null]);
        $employee->forceFill(['supervisor_id' => null])->save();
        $supervisorEmployee = Employee::factory()->create(['user_id' => $supervisor->id]);
        $employee->update(['supervisor_id' => $supervisorEmployee->id]);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $employee->checklistSkillIds());

        // Drop $b, add $c.
        $this->actingAs($supervisor)
            ->put(route('employees.skill-checklist.update', $employee), ['skills' => [$a->id, $c->id]])
            ->assertRedirect(route('employees.show', $employee));

        $this->assertEqualsCanonicalizing([$a->id, $c->id], $employee->fresh()->checklistSkillIds());
        $this->assertDatabaseHas('employee_skill_overrides', ['employee_id' => $employee->id, 'skill_id' => $b->id, 'action' => 'remove']);
        $this->assertDatabaseHas('employee_skill_overrides', ['employee_id' => $employee->id, 'skill_id' => $c->id, 'action' => 'add']);
        $this->assertDatabaseMissing('employee_skill_overrides', ['skill_id' => $a->id]);

        // A later change to the position default still flows through.
        $position->skills()->attach(Skill::factory()->create());
        $this->assertCount(3, $employee->fresh()->checklistSkillIds());
    }

    public function test_staff_cannot_review_a_checklist(): void
    {
        $staff = $this->user(RoleName::MaintenanceStaff);
        $employee = Employee::factory()->create(['user_id' => $staff->id]);

        $this->actingAs($staff)->get(route('employees.skill-checklist.edit', $employee))->assertForbidden();
    }

    public function test_manager_pages_render(): void
    {
        $manager = $this->user(RoleName::MaintenanceManager);
        $position = Position::factory()->create();
        Skill::factory()->count(2)->create();
        $employee = Employee::factory()->create(['position_id' => $position->id]);

        $this->actingAs($manager)->get(route('skills.catalog.index'))->assertOk();
        $this->actingAs($manager)->get(route('skills.catalog.template'))->assertOk();
        $this->actingAs($manager)->get(route('skills.catalog.export'))->assertOk();
        $this->actingAs($manager)->get(route('skills.position-defaults.index'))->assertOk();
        $this->actingAs($manager)->get(route('skills.position-defaults.edit', $position))->assertOk();
        $admin = $this->user(RoleName::Administrator);
        $this->actingAs($admin)->get(route('employees.skill-checklist.edit', $employee))->assertOk();
        $this->actingAs($admin)->get(route('employees.show', $employee))->assertOk()->assertSee('Review checklist');
    }
}
