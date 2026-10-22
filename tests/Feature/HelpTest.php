<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\SidebarMenu;
use App\Models\User;
use App\Support\MenuAccess;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_visitors_must_log_in_to_see_help(): void
    {
        $this->get(route('help.index'))->assertRedirect();
    }

    public function test_staff_sees_the_guide_without_admin_or_developer_sections(): void
    {
        $response = $this->actingAs($this->userWithRole(RoleName::MaintenanceStaff))->get(route('help.index'));

        $response->assertOk()
            ->assertSee('Tutorial Penggunaan')
            ->assertSee('Memulai')
            ->assertDontSee('Provisioning akun karyawan')
            ->assertDontSee('Catatan untuk developer');
    }

    public function test_administrator_also_sees_the_admin_section(): void
    {
        $this->actingAs($this->userWithRole(RoleName::Administrator))
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Provisioning akun karyawan')
            ->assertDontSee('Catatan untuk developer');
    }

    public function test_guest_role_can_open_help(): void
    {
        $this->actingAs($this->userWithRole(RoleName::Guest))->get(route('help.index'))->assertOk();
    }

    public function test_help_link_is_in_the_sidebar(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MaintenanceStaff))
            ->get(route('help.index'))
            ->assertSee(route('help.index'), false);
    }

    public function test_help_can_be_hidden_per_user(): void
    {
        $user = $this->userWithRole(RoleName::MaintenanceStaff);
        $user->hiddenMenus()->create(['menu_key' => SidebarMenu::Help->value]);

        $this->actingAs($user)->get(route('help.index'))->assertRedirect(route('dashboard'));
    }

    public function test_guest_sees_the_guest_guide_and_not_account_sections(): void
    {
        $this->actingAs($this->userWithRole(RoleName::Guest))
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Panduan Tamu')
            ->assertDontSee('Login pertama: wajib ganti password')
            ->assertDontSee('Face Login</h3>', false)
            ->assertDontSee('Provisioning akun karyawan')
            ->assertDontSee('Mengelola kalender')
            ->assertDontSee('Shift Comm</h3>', false)
            ->assertDontSee('Papan tugas gaya');
    }

    public function test_non_guests_do_not_see_the_guest_guide(): void
    {
        $this->actingAs($this->userWithRole(RoleName::MaintenanceStaff))
            ->get(route('help.index'))
            ->assertDontSee('Panduan Tamu (Guest)')
            ->assertSee('Login pertama: wajib ganti password');
    }

    public function test_staff_only_sees_sections_for_menus_they_can_open(): void
    {
        $staff = $this->userWithRole(RoleName::MaintenanceStaff);

        $this->actingAs($staff)->get(route('help.index'))
            ->assertSee('Papan tugas gaya')
            ->assertDontSee('Catatan perubahan yang tidak bisa dihapus')
            ->assertDontSee('Pengaturan aplikasi, saat ini');
    }

    public function test_hiding_a_menu_hides_its_section_from_the_guide(): void
    {
        $user = $this->userWithRole(RoleName::MaintenanceStaff);
        $this->actingAs($user)->get(route('help.index'))->assertSee('Papan tugas gaya');

        $user->hiddenMenus()->create(['menu_key' => SidebarMenu::Tasks->value]);

        $this->actingAs($user->fresh())->get(route('help.index'))->assertDontSee('Papan tugas gaya');
    }

    public function test_edit_instructions_only_show_to_users_who_can_edit(): void
    {
        $this->actingAs($this->userWithRole(RoleName::Administrator))
            ->get(route('help.index'))->assertSee('Menambah karyawan');

        $this->actingAs($this->userWithRole(RoleName::Guest))
            ->get(route('help.index'))->assertDontSee('Menambah karyawan');
    }

    public function test_menu_access_matches_the_sidebar_for_every_role(): void
    {
        $routes = [
            'tasks' => 'tasks.index', 'shift-comm' => 'shift-comm.index', 'employees' => 'employees.index',
            'organization-chart' => 'organization-chart.index', 'qr-codes' => 'employees.qr-codes',
            'plant-manager' => 'plant-manager.edit', 'organization' => 'organization.landing',
            'overtime' => 'overtime.index', 'job-descriptions' => 'job-descriptions.index',
            'skills' => 'skills.landing', 'skill-matrix' => 'skill-matrix.index',
            'competency-gap' => 'competency-gap-analysis.index', 'training-management' => 'training.programs.index',
            'training-calendar' => 'training.calendar', 'training-records' => 'training.records.index',
            'certificates' => 'certificates.index', 'recertification' => 'certificates.recertification',
            'signatories' => 'signatories.index', 'development-plans' => 'development-plans.index',
            'lototo' => 'safety.lototo.index', 'reports' => 'reports.index', 'news' => 'news.index',
        ];

        foreach (RoleName::cases() as $role) {
            $user = $this->userWithRole($role);
            $visible = array_map(fn ($m) => $m->value, MenuAccess::visibleMenus($user));
            $html = $this->actingAs($user)->get(route('help.index'))->getContent();

            foreach ($routes as $menu => $routeName) {
                $this->assertSame(
                    in_array($menu, $visible, true),
                    str_contains($html, 'href="'.route($routeName).'"'),
                    "Sidebar and MenuAccess disagree on '{$menu}' for {$role->value}"
                );
            }
        }
    }
}
