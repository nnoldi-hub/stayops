<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_model_uses_two_factor_trait(): void
    {
        $this->assertContains(
            TwoFactorAuthenticatable::class,
            class_uses_recursive(User::class)
        );
    }

    public function test_two_factor_columns_exist_on_users_table(): void
    {
        $columns = \Schema::getColumnListing('users');

        $this->assertContains('two_factor_secret', $columns);
        $this->assertContains('two_factor_recovery_codes', $columns);
        $this->assertContains('two_factor_confirmed_at', $columns);
    }

    public function test_superadmin_without_2fa_is_redirected_to_setup_when_accessing_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/two-factor-setup');
    }

    public function test_host_without_2fa_is_redirected_to_setup_when_accessing_host_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Host');

        $response = $this->actingAs($user)->get('/host');

        $response->assertRedirect('/two-factor-setup');
    }

    public function test_provider_does_not_require_2fa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Provider');

        $response = $this->actingAs($user)->get('/provider');

        $response->assertOk();
    }

    public function test_user_with_confirmed_2fa_can_access_admin(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('SuperAdmin');

        $response = $this->actingAs($user)->get('/admin');

        // Filament admin returns 200 (the dashboard) or 302 to /admin/login if not panel-authed,
        // but never to /two-factor-setup.
        $this->assertNotEquals('/two-factor-setup', $response->headers->get('Location'));
    }

    public function test_two_factor_setup_page_is_reachable_without_loop(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Host');

        $response = $this->actingAs($user)->get('/two-factor-setup');

        $response->assertOk();
        $response->assertSee('autentificare în doi pași', false);
    }
}
