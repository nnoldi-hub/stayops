<?php

namespace Tests\Feature;

use App\Filament\Provider\Resources\TaskResource;
use App\Filament\Provider\Resources\Tasks\Pages\ListTasks;
use App\Filament\Provider\Widgets\RecentCompletedTasksWidget;
use App\Filament\Provider\Widgets\TaskStatsWidget;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProviderPanelTest extends TestCase
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

    public function test_provider_can_access_provider_panel(): void
    {
        $user = $this->makeProviderUser();

        $this->actingAs($user)
            ->get('/provider')
            ->assertOk();
    }

    public function test_non_provider_cannot_access_provider_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Host');

        $this->actingAs($user)
            ->get('/provider')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_provider_panel(): void
    {
        $this->get('/provider')
            ->assertRedirect('/provider/login');
    }

    public function test_provider_only_sees_own_tasks(): void
    {
        $user = $this->makeProviderUser();
        $provider = $user->provider;

        $otherProvider = Provider::factory()->create();
        $property = Property::factory()->create();

        $ownTask = Task::factory()->create([
            'property_id' => $property->id,
            'provider_id' => $provider->id,
        ]);
        $otherTask = Task::factory()->create([
            'property_id' => $property->id,
            'provider_id' => $otherProvider->id,
        ]);

        $this->actingAs($user);

        $ids = TaskResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($ownTask->id));
        $this->assertFalse($ids->contains($otherTask->id));
    }

    public function test_provider_sees_task_assigned_directly_to_them(): void
    {
        $user = $this->makeProviderUser();
        $property = Property::factory()->create();

        $assignedTask = Task::factory()->create([
            'property_id' => $property->id,
            'provider_id' => null,
            'assigned_to_user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $ids = TaskResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($assignedTask->id));
    }

    public function test_provider_dashboard_renders(): void
    {
        $user = $this->makeProviderUser();

        $this->actingAs($user)
            ->get('/provider')
            ->assertOk()
            ->assertSee('Sarcini');
    }

    public function test_provider_widgets_are_registered(): void
    {
        $widgets = Filament::getPanel('provider')->getWidgets();

        $this->assertContains(TaskStatsWidget::class, $widgets);
        $this->assertContains(RecentCompletedTasksWidget::class, $widgets);
    }

    public function test_task_stats_widget_counts_provider_tasks(): void
    {
        $user = $this->makeProviderUser();
        $provider = $user->provider;

        Task::factory()->count(3)->create(['provider_id' => $provider->id, 'status' => 'open']);
        Task::factory()->count(7)->create(['provider_id' => $provider->id, 'status' => 'assigned']);
        Task::factory()->count(11)->create(['provider_id' => $provider->id, 'status' => 'in_progress']);
        Task::factory()->count(13)->create(['provider_id' => $provider->id, 'status' => 'completed']);
        Task::factory()->count(5)->create(['provider_id' => $provider->id, 'status' => 'verified']);
        Task::factory()->count(4)->create(['status' => 'open']);

        $this->actingAs($user);

        Livewire::test(TaskStatsWidget::class)
            ->assertSeeText('10')
            ->assertSeeText('11')
            ->assertSeeText('18');
    }

    public function test_recent_completed_tasks_widget_lists_completed_tasks(): void
    {
        $user = $this->makeProviderUser();
        $provider = $user->provider;

        $done = Task::factory()->create([
            'provider_id' => $provider->id,
            'status' => 'completed',
            'title' => 'Curatenie finalizata',
        ]);
        $open = Task::factory()->create([
            'provider_id' => $provider->id,
            'status' => 'open',
            'title' => 'Sarcina deschisa',
        ]);

        $this->actingAs($user);

        Livewire::test(RecentCompletedTasksWidget::class)
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$open]);
    }

    public function test_task_list_can_be_filtered_by_status(): void
    {
        $user = $this->makeProviderUser();
        $provider = $user->provider;

        $open = Task::factory()->create(['provider_id' => $provider->id, 'status' => 'open']);
        $progress = Task::factory()->create(['provider_id' => $provider->id, 'status' => 'in_progress']);

        $this->actingAs($user);

        Livewire::test(ListTasks::class)
            ->filterTable('status', 'in_progress')
            ->assertCanSeeTableRecords([$progress])
            ->assertCanNotSeeTableRecords([$open]);
    }
}
