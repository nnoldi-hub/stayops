<?php

namespace Tests\Feature;

use App\Filament\Resources\Hosts\Pages\ListHosts;
use App\Models\Host;
use App\Models\User;
use App\Services\ImpersonationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    private function makeSuperAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->assignRole('SuperAdmin');
        $this->actingAs($admin);

        return $admin;
    }

    private function makeHostUser(bool $withTwoFactor = true): User
    {
        /** @var User $user */
        $user = User::factory()->create($withTwoFactor ? [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ] : []);
        $user->assignRole('Host');
        Host::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_superadmin_can_impersonate_a_host(): void
    {
        $admin = $this->makeSuperAdmin();
        $host = $this->makeHostUser();

        $this->post(route('impersonation.start', $host), ['reason' => 'Suport tehnic'])
            ->assertRedirect(route('impersonation.landing'));

        $this->assertAuthenticatedAs($host);
        $this->assertDatabaseHas('impersonation_logs', [
            'admin_id' => $admin->id,
            'target_id' => $host->id,
            'reason' => 'Suport tehnic',
        ]);
    }

    public function test_impersonation_landing_redirects_target_to_their_panel(): void
    {
        $this->makeSuperAdmin();
        $host = $this->makeHostUser();

        $this->post(route('impersonation.start', $host), ['reason' => 'Test']);

        $this->get(route('impersonation.landing'))->assertRedirect('/host');
    }

    public function test_non_superadmin_cannot_start_impersonation(): void
    {
        $host = $this->makeHostUser();
        $target = $this->makeHostUser();

        $this->actingAs($host)
            ->post(route('impersonation.start', $target), ['reason' => 'Hack'])
            ->assertForbidden();

        $this->assertAuthenticatedAs($host);
        $this->assertDatabaseCount('impersonation_logs', 0);
    }

    public function test_superadmin_cannot_impersonate_themselves(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->post(route('impersonation.start', $admin), ['reason' => 'Test'])
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('impersonation_logs', 0);
    }

    public function test_superadmin_cannot_impersonate_another_superadmin(): void
    {
        $this->makeSuperAdmin();

        /** @var User $otherAdmin */
        $otherAdmin = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $otherAdmin->assignRole('SuperAdmin');

        $this->post(route('impersonation.start', $otherAdmin), ['reason' => 'Test'])
            ->assertForbidden();

        $this->assertDatabaseCount('impersonation_logs', 0);
    }

    public function test_cannot_start_a_second_impersonation_while_impersonating(): void
    {
        $this->makeSuperAdmin();
        $host = $this->makeHostUser();
        $other = $this->makeHostUser();

        $this->post(route('impersonation.start', $host), ['reason' => 'Test']);

        $this->post(route('impersonation.start', $other), ['reason' => 'Test'])
            ->assertForbidden();

        $this->assertAuthenticatedAs($host);
        $this->assertDatabaseCount('impersonation_logs', 1);
    }

    public function test_superadmin_can_stop_impersonation(): void
    {
        $admin = $this->makeSuperAdmin();
        $host = $this->makeHostUser();

        $this->post(route('impersonation.start', $host), ['reason' => 'Test']);
        $this->assertAuthenticatedAs($host);

        $this->post(route('impersonation.stop'))
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has(ImpersonationService::SESSION_KEY));
        $this->assertDatabaseMissing('impersonation_logs', ['ended_at' => null]);
    }

    public function test_stopping_without_active_impersonation_is_forbidden(): void
    {
        $this->makeSuperAdmin();

        $this->post(route('impersonation.stop'))->assertForbidden();
    }

    public function test_impersonation_banner_is_rendered_on_host_pages(): void
    {
        $this->makeSuperAdmin();
        $host = $this->makeHostUser();

        $this->post(route('impersonation.start', $host), ['reason' => 'Test']);

        $this->get('/host')
            ->assertOk()
            ->assertSee('data-testid="impersonation-banner"', false)
            ->assertSee($host->name)
            ->assertSee('impersonation-stop-form', false);
    }

    public function test_impersonated_host_without_two_factor_is_not_redirected_to_setup(): void
    {
        $this->makeSuperAdmin();
        $host = $this->makeHostUser(withTwoFactor: false);

        $this->post(route('impersonation.start', $host), ['reason' => 'Test']);

        $this->get('/host')->assertOk();
    }

    public function test_filament_host_table_action_starts_impersonation(): void
    {
        $this->makeSuperAdmin();
        $hostUser = $this->makeHostUser();
        $host = $hostUser->host;

        Livewire::test(ListHosts::class)
            ->callTableAction('impersonate', $host, data: ['reason' => 'Suport'])
            ->assertRedirect(route('impersonation.landing'));

        $this->assertAuthenticatedAs($hostUser);
        $this->assertDatabaseHas('impersonation_logs', [
            'target_id' => $hostUser->id,
            'reason' => 'Suport',
        ]);
    }
}
