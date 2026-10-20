<?php

namespace Tests\Feature;

use App\Filament\Resources\Hosts\Pages\CreateHost;
use App\Filament\Resources\Providers\Pages\CreateProvider;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Host;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    private function actAsAdmin(): User
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

    public function test_admin_panel_shows_users_navigation(): void
    {
        $this->actAsAdmin();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Utilizatori')
            ->assertSee('Conturi');
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $this->actAsAdmin();

        $providerRoleId = Role::where('name', 'Provider')->value('id');

        Livewire::test(CreateUser::class)
            ->set('data.name', 'Utilizator Nou')
            ->set('data.email', 'nou@stayops.local')
            ->set('data.password', 'secret-password')
            ->set('data.roles', [$providerRoleId])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'nou@stayops.local')->firstOrFail();

        $this->assertTrue($user->hasRole('Provider'));
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_suspend_action_suspends_and_blocks_access(): void
    {
        $this->actAsAdmin();
        $victim = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callTableAction('suspend', $victim, data: ['reason' => 'Încălcare termeni']);

        $victim->refresh();

        $this->assertTrue($victim->isSuspended());
        $this->assertSame('Încălcare termeni', $victim->suspended_reason);
        $this->assertNotNull($victim->suspended_at);

        $this->actingAs($victim);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_suspended_user_is_logged_out_right_after_login(): void
    {
        $victim = User::factory()->create();
        $victim->suspend('Abuz constatat');

        $this->post('/login', [
            'email' => $victim->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_activate_action_reactivates_account(): void
    {
        $this->actAsAdmin();
        $victim = User::factory()->create();
        $victim->suspend('Test suspendare');

        Livewire::test(ListUsers::class)
            ->callTableAction('activate', $victim);

        $victim->refresh();

        $this->assertFalse($victim->isSuspended());
        $this->assertNull($victim->suspended_at);
        $this->assertNull($victim->suspended_reason);
    }

    public function test_suspend_action_is_hidden_for_superadmin_targets(): void
    {
        $this->actAsAdmin();
        $target = User::factory()->create();
        $target->assignRole('SuperAdmin');

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('suspend', $target);
    }

    public function test_password_reset_link_can_be_sent(): void
    {
        Notification::fake();
        $this->actAsAdmin();
        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->callTableAction('sendPasswordReset', $user);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_view_page_renders_account_details(): void
    {
        $this->actAsAdmin();
        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertOk()
            ->assertSee('Securitate & acces');
    }

    public function test_host_profile_creation_assigns_host_role(): void
    {
        $this->actAsAdmin();
        $user = User::factory()->create();

        Livewire::test(CreateHost::class)
            ->set('data.user_id', $user->id)
            ->set('data.company_name', 'Gazda Test SRL')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue($user->fresh()->hasRole('Host'));
    }

    public function test_provider_profile_creation_assigns_provider_role(): void
    {
        $this->actAsAdmin();
        $user = User::factory()->create();

        Livewire::test(CreateProvider::class)
            ->set('data.user_id', $user->id)
            ->set('data.company_name', 'Firmă Test SRL')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue($user->fresh()->hasRole('Provider'));
    }

    public function test_deletion_guards_protect_admin_and_linked_accounts(): void
    {
        $admin = $this->actAsAdmin();

        $otherSuperAdmin = User::factory()->create();
        $otherSuperAdmin->assignRole('SuperAdmin');

        $guest = User::factory()->create();

        $hostUser = User::factory()->create();
        Host::factory()->create(['user_id' => $hostUser->id]);

        $this->assertNull(UserResource::deletionBlocker($guest));
        $this->assertStringContainsString('propriul', (string) UserResource::deletionBlocker($admin));
        $this->assertStringContainsString('SuperAdmin', (string) UserResource::deletionBlocker($otherSuperAdmin));
        $this->assertStringContainsString('profil', (string) UserResource::deletionBlocker($hostUser));
    }
}
