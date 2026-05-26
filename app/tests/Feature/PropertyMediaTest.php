<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_can_attach_photo_to_photos_collection(): void
    {
        Storage::fake('public');

        $property = Property::factory()->for(Host::factory())->create();

        $media = $property
            ->addMedia(UploadedFile::fake()->image('villa.jpg', 1200, 800))
            ->toMediaCollection('photos');

        $this->assertSame('photos', $media->collection_name);
        $this->assertSame($property->id, $media->model_id);
        $this->assertSame(Property::class, $media->model_type);
        $this->assertCount(1, $property->fresh()->getMedia('photos'));
        Storage::disk('public')->assertExists($media->id.'/villa.jpg');
    }

    public function test_property_can_attach_multiple_photos(): void
    {
        Storage::fake('public');

        $property = Property::factory()->for(Host::factory())->create();

        foreach (['a.jpg', 'b.jpg', 'c.jpg'] as $name) {
            $property->addMedia(UploadedFile::fake()->image($name))->toMediaCollection('photos');
        }

        $this->assertCount(3, $property->fresh()->getMedia('photos'));
    }

    public function test_floor_plans_collection_is_single_file(): void
    {
        Storage::fake('public');

        $property = Property::factory()->for(Host::factory())->create();

        $property->addMedia(UploadedFile::fake()->image('plan1.png'))->toMediaCollection('floor_plans');
        $property->addMedia(UploadedFile::fake()->image('plan2.png'))->toMediaCollection('floor_plans');

        // singleFile() ensures previous is replaced — only 1 remains
        $this->assertCount(1, $property->fresh()->getMedia('floor_plans'));
        $this->assertSame('plan2.png', $property->fresh()->getFirstMedia('floor_plans')->file_name);
    }

    public function test_photo_url_is_generated(): void
    {
        Storage::fake('public');

        $property = Property::factory()->for(Host::factory())->create();
        $media = $property->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('photos');

        $this->assertNotEmpty($media->getUrl());
        $this->assertStringContainsString('cover.jpg', $media->getUrl());
    }
}
