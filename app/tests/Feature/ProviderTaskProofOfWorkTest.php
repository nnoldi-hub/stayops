<?php

namespace Tests\Feature;

use App\Filament\Provider\Resources\Tasks\Pages\ListTasks;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProviderTaskProofOfWorkTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

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

    public function test_task_can_store_before_and_after_photos(): void
    {
        Storage::fake('public');

        $task = Task::factory()->create();

        $this->assertFalse($task->hasAfterPhotos());

        $png = base64_decode(self::PNG);

        $task->addMediaFromString($png)->usingFileName('before.png')->toMediaCollection('before_photos');
        $task->addMediaFromString($png)->usingFileName('after.png')->toMediaCollection('after_photos');

        $task->refresh();

        $this->assertCount(1, $task->getMedia('before_photos'));
        $this->assertCount(1, $task->getMedia('after_photos'));
        $this->assertTrue($task->hasAfterPhotos());
    }

    public function test_complete_action_requires_after_photos(): void
    {
        $user = $this->makeProviderUser();
        $task = $this->makeTask($user, ['status' => 'in_progress']);

        $this->actingAs($user);

        Livewire::test(ListTasks::class)
            ->callTableAction('complete', $task)
            ->assertNotified();

        $this->assertSame('in_progress', $task->fresh()->status);
    }

    public function test_complete_action_completes_task_with_after_photos(): void
    {
        Storage::fake('public');

        $user = $this->makeProviderUser();
        $task = $this->makeTask($user, ['status' => 'in_progress']);

        $task->addMediaFromString(base64_decode(self::PNG))
            ->usingFileName('after.png')
            ->toMediaCollection('after_photos');

        $this->actingAs($user);

        Livewire::test(ListTasks::class)
            ->callTableAction('complete', $task)
            ->assertHasNoTableActionErrors();

        $task->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }
}
