<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\SidebarMenu;
use App\Models\User;
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
}
