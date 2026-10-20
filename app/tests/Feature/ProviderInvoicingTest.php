<?php

namespace Tests\Feature;

use App\Filament\Provider\Pages\Invoicing;
use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProviderInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel('provider');
    }

    private function makeProviderUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Provider');
        Provider::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function makeTask(User $user, array $attributes = []): Task
    {
        return Task::factory()->create([
            'property_id' => Property::factory(),
            'provider_id' => $user->provider->id,
            ...$attributes,
        ]);
    }

    public function test_provider_can_access_invoicing_page(): void
    {
        $user = $this->makeProviderUser();

        $this->actingAs($user)
            ->get('/provider/invoicing')
            ->assertOk()
            ->assertSee('Raport de facturare');
    }

    public function test_non_provider_cannot_access_invoicing_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Host');

        $this->actingAs($user)
            ->get('/provider/invoicing')
            ->assertForbidden();
    }

    public function test_invoicing_lists_only_completed_and_verified_own_tasks(): void
    {
        $user = $this->makeProviderUser();

        $completed = $this->makeTask($user, ['status' => 'completed', 'completed_at' => now()]);
        $verified = $this->makeTask($user, ['status' => 'verified', 'completed_at' => now()]);
        $open = $this->makeTask($user, ['status' => 'open']);
        $inProgress = $this->makeTask($user, ['status' => 'in_progress']);

        $otherUser = $this->makeProviderUser();
        $otherProvider = $this->makeTask($otherUser, ['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($user);

        Livewire::test(Invoicing::class)
            ->assertCanSeeTableRecords([$completed, $verified])
            ->assertCanNotSeeTableRecords([$open, $inProgress, $otherProvider]);
    }

    public function test_invoicing_can_be_filtered_by_host(): void
    {
        $user = $this->makeProviderUser();

        $hostA = Host::factory()->create();
        $hostB = Host::factory()->create();

        $taskA = $this->makeTask($user, [
            'property_id' => Property::factory()->create(['host_id' => $hostA->id]),
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        $taskB = $this->makeTask($user, [
            'property_id' => Property::factory()->create(['host_id' => $hostB->id]),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(Invoicing::class)
            ->filterTable('host', $hostA->id)
            ->assertCanSeeTableRecords([$taskA])
            ->assertCanNotSeeTableRecords([$taskB]);
    }

    public function test_invoicing_can_be_filtered_by_month(): void
    {
        $user = $this->makeProviderUser();

        $thisMonth = $this->makeTask($user, ['status' => 'completed', 'completed_at' => now()]);
        $lastMonth = $this->makeTask($user, [
            'status' => 'completed',
            'completed_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(5),
        ]);

        $this->actingAs($user);

        Livewire::test(Invoicing::class)
            ->filterTable('month', now()->format('Y-m'))
            ->assertCanSeeTableRecords([$thisMonth])
            ->assertCanNotSeeTableRecords([$lastMonth]);
    }
}
