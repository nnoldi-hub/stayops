<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    private function actAsSuperAdmin(): User
    {
        /** @var User $admin */
        $admin = User::query()->where('email', 'admin@stayops.local')->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_superadmin_sees_command_center_dashboard(): void
    {
        $admin = $this->actAsSuperAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Command Center')
            ->assertSee($admin->name)
            ->assertSee('Iată starea platformei StayOps')
            ->assertSee('#0f172a')
            ->assertSee('fonts.bunny.net');
    }

    public function test_navigation_is_grouped_for_the_command_center(): void
    {
        $this->actAsSuperAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Marketplace')
            ->assertSee('Operațional')
            ->assertSee('Financiar')
            ->assertSee('Configurare')
            ->assertSee('Rapoarte')
            ->assertSee('Abonamente');
    }

    public function test_superadmin_can_open_report_pages(): void
    {
        $this->actAsSuperAdmin();

        $this->get('/admin/reports/financial')->assertOk()->assertSee('Raport financiar');
        $this->get('/admin/reports/operational')->assertOk()->assertSee('Raport operațional');
        $this->get('/admin/reports/marketplace')->assertOk()->assertSee('Raport marketplace');
    }

    public function test_superadmin_can_open_messages_page_with_sidebar(): void
    {
        $this->actAsSuperAdmin();

        $this->get('/admin/messages')
            ->assertOk()
            ->assertSee('Mesaje')
            ->assertSee('fi-sidebar');
    }

    public function test_host_cannot_access_admin_panel(): void
    {
        /** @var User $host */
        $host = User::query()->where('email', 'host@stayops.local')->firstOrFail();
        $this->actingAs($host);

        $this->get('/admin')->assertForbidden();
    }
}
