<?php

namespace Tests\Feature;

use App\Filament\Resources\Hosts\Pages\ListHosts;
use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Models\Host;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KycApprovalTest extends TestCase
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

    public function test_admin_can_approve_host_kyc(): void
    {
        config(['audit.console' => true]);
        $this->actAsAdmin();
        $host = Host::factory()->pending()->create();

        Livewire::test(ListHosts::class)
            ->callTableAction('approveKyc', $host);

        $this->assertSame('approved', $host->fresh()->kyc_status);
        $this->assertDatabaseHas('audits', [
            'auditable_id' => $host->id,
            'auditable_type' => Host::class,
            'event' => 'updated',
        ]);
    }

    public function test_admin_can_reject_host_kyc(): void
    {
        $this->actAsAdmin();
        $host = Host::factory()->create(['kyc_status' => 'submitted']);

        Livewire::test(ListHosts::class)
            ->callTableAction('rejectKyc', $host);

        $this->assertSame('rejected', $host->fresh()->kyc_status);
    }

    public function test_approve_action_is_hidden_for_already_approved_host(): void
    {
        $this->actAsAdmin();
        $host = Host::factory()->create(['kyc_status' => 'approved']);

        Livewire::test(ListHosts::class)
            ->assertTableActionHidden('approveKyc', $host);
    }

    public function test_admin_can_approve_provider_kyc(): void
    {
        $this->actAsAdmin();
        $provider = Provider::factory()->pending()->create();

        Livewire::test(ListProviders::class)
            ->callTableAction('approveKyc', $provider);

        $this->assertSame('approved', $provider->fresh()->kyc_status);
    }

    public function test_admin_can_reject_provider_kyc(): void
    {
        $this->actAsAdmin();
        $provider = Provider::factory()->create(['kyc_status' => 'submitted']);

        Livewire::test(ListProviders::class)
            ->callTableAction('rejectKyc', $provider);

        $this->assertSame('rejected', $provider->fresh()->kyc_status);
    }

    public function test_admin_can_list_providers(): void
    {
        $this->actAsAdmin();
        $provider = Provider::factory()->create(['company_name' => 'Curățenie Total SRL']);

        $this->get('/admin/providers')
            ->assertOk()
            ->assertSee('Curățenie Total SRL')
            ->assertSee($provider->user->name);
    }

    public function test_admin_can_edit_provider(): void
    {
        $this->actAsAdmin();
        $provider = Provider::factory()->create();

        $this->get("/admin/providers/{$provider->id}/edit")->assertOk();
    }

    public function test_non_superadmin_cannot_access_providers_resource(): void
    {
        /** @var User $host */
        $host = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $host->assignRole('Host');
        $this->actingAs($host);

        $this->get('/admin/providers')->assertForbidden();
    }
}
