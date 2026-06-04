<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HostPropertyGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function actAsHost(): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret'       => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');
        $host = Host::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return [$user, $host];
    }

    private function makeProperty(Host $host): Property
    {
        return Property::factory()->for($host)->create(['status' => 'published']);
    }

    // ── Upload photos ────────────────────────────────────────────────────────

    public function test_host_can_upload_photos_to_property(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('photo2.png', 800, 600);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('newPhotos', [$file1, $file2])
            ->call('uploadPhotos');

        $this->assertCount(2, $property->fresh()->getMedia('photos'));
    }

    public function test_upload_photos_rejects_non_image_files(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('newPhotos', [$pdf])
            ->call('uploadPhotos')
            ->assertHasErrors(['newPhotos.*']);

        $this->assertCount(0, $property->fresh()->getMedia('photos'));
    }

    public function test_host_can_delete_a_photo(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $property->addMedia($file->getRealPath())
            ->usingFileName('photo.jpg')
            ->toMediaCollection('photos');

        $mediaId = $property->getFirstMedia('photos')->id;

        Volt::test('host.properties.edit', ['property' => $property])
            ->call('deletePhoto', $mediaId);

        $this->assertCount(0, $property->fresh()->getMedia('photos'));
    }

    // ── Floor plan ───────────────────────────────────────────────────────────

    public function test_host_can_upload_floor_plan(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $file = UploadedFile::fake()->image('plan.jpg', 1200, 800);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('newFloorPlan', $file)
            ->call('uploadFloorPlan');

        $this->assertNotNull($property->fresh()->getFirstMedia('floor_plans'));
    }

    public function test_floor_plan_upload_replaces_existing(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $first = UploadedFile::fake()->image('first.jpg', 800, 600);
        $property->addMedia($first->getRealPath())
            ->usingFileName('first.jpg')
            ->toMediaCollection('floor_plans');

        $second = UploadedFile::fake()->image('second.jpg', 800, 600);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('newFloorPlan', $second)
            ->call('uploadFloorPlan');

        $fp = $property->fresh()->getMedia('floor_plans');
        $this->assertCount(1, $fp);
        $this->assertEquals('second.jpg', $fp->first()->file_name);
    }

    public function test_host_can_delete_floor_plan(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $file = UploadedFile::fake()->image('plan.jpg', 800, 600);
        $property->addMedia($file->getRealPath())
            ->usingFileName('plan.jpg')
            ->toMediaCollection('floor_plans');

        Volt::test('host.properties.edit', ['property' => $property])
            ->call('deleteFloorPlan');

        $this->assertCount(0, $property->fresh()->getMedia('floor_plans'));
    }

    public function test_another_host_cannot_access_edit_page(): void
    {
        [, $host] = $this->actAsHost();
        $otherHost = Host::factory()->create();
        $property  = $this->makeProperty($otherHost);

        Volt::test('host.properties.edit', ['property' => $property])
            ->assertForbidden();
    }
}
