<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(?string $role = null): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        return $user;
    }

    public function test_superadmin_is_redirected_to_admin_panel(): void
    {
        $user = $this->makeUser('SuperAdmin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_host_is_redirected_to_host_dashboard(): void
    {
        $user = $this->makeUser('Host');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/host');
    }

    public function test_provider_is_redirected_to_provider_dashboard(): void
    {
        $user = $this->makeUser('Provider');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/provider');
    }

    public function test_guest_role_falls_back_to_dashboard(): void
    {
        $user = $this->makeUser('Guest');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_user_without_role_falls_back_to_dashboard(): void
    {
        $user = $this->makeUser(null);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }
}
