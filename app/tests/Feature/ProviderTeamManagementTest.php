<?php

namespace Tests\Feature;

use App\Filament\Provider\Resources\Tasks\Pages\EditTask;
use App\Filament\Provider\Resources\Teams\Pages\CreateTeam;
use App\Filament\Provider\Resources\Teams\Pages\EditTeam;
use App\Filament\Provider\Resources\Teams\Pages\ListTeams;
use App\Filament\Provider\Resources\Teams\RelationManagers\MembersRelationManager;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProviderTeamManagementTest extends TestCase
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

    public function test_provider_can_access_teams_page(): void
    {
        $user = $this->makeProviderUser();

        $this->actingAs($user)
            ->get('/provider/teams')
            ->assertOk();
    }

    public function test_non_provider_cannot_access_teams_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Host');

        $this->actingAs($user)
            ->get('/provider/teams')
            ->assertForbidden();
    }

    public function test_provider_only_sees_own_teams(): void
    {
        $user = $this->makeProviderUser();
        $ownTeam = Team::factory()->create(['provider_id' => $user->provider->id]);
        $otherTeam = Team::factory()->create();

        $this->actingAs($user);

        Livewire::test(ListTeams::class)
            ->assertCanSeeTableRecords([$ownTeam])
            ->assertCanNotSeeTableRecords([$otherTeam]);
    }

    public function test_provider_can_create_team_assigned_to_themselves(): void
    {
        $user = $this->makeProviderUser();

        $this->actingAs($user);

        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Echipa Alfa',
                'description' => 'Curățenie generală',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('teams', [
            'name' => 'Echipa Alfa',
            'provider_id' => $user->provider->id,
        ]);
    }

    public function test_provider_can_add_member_to_team_via_relation_manager(): void
    {
        $user = $this->makeProviderUser();
        $team = Team::factory()->create(['provider_id' => $user->provider->id]);
        $employee = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(MembersRelationManager::class, [
            'ownerRecord' => $team,
            'pageClass' => EditTeam::class,
        ])
            ->callTableAction('create', data: [
                'user_id' => $employee->id,
                'role' => 'lead',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'role' => 'lead',
        ]);
        $this->assertCount(1, $team->fresh()->members);
    }

    public function test_provider_can_delegate_task_to_team_member(): void
    {
        $user = $this->makeProviderUser();
        $team = Team::factory()->create(['provider_id' => $user->provider->id]);
        $employee = User::factory()->create();
        TeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $employee->id]);

        $task = Task::factory()->create([
            'property_id' => Property::factory(),
            'provider_id' => $user->provider->id,
            'assigned_to_user_id' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
            ->fillForm(['assigned_to_user_id' => $employee->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($employee->id, $task->fresh()->assigned_to_user_id);
    }
}
