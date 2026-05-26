<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_properties_index_is_publicly_accessible(): void
    {
        $host = Host::factory()->create();
        Property::factory()->for($host)->create([
            'title' => 'Vila Bucegi',
            'city' => 'Bușteni',
            'status' => 'published',
        ]);

        $this->get('/properties')
            ->assertOk()
            ->assertSee('Proprietăți disponibile')
            ->assertSee('Vila Bucegi');
    }

    public function test_properties_index_excludes_unpublished(): void
    {
        $host = Host::factory()->create();
        Property::factory()->for($host)->create([
            'title' => 'Vila Publica',
            'status' => 'published',
        ]);
        Property::factory()->for($host)->create([
            'title' => 'Vila Ascunsa',
            'status' => 'draft',
        ]);

        $this->get('/properties')
            ->assertOk()
            ->assertSee('Vila Publica')
            ->assertDontSee('Vila Ascunsa');
    }

    public function test_properties_index_filters_by_city(): void
    {
        $host = Host::factory()->create();
        Property::factory()->for($host)->create(['title' => 'Brașov Loft', 'city' => 'Brașov', 'status' => 'published']);
        Property::factory()->for($host)->create(['title' => 'Cluj Apt',   'city' => 'Cluj-Napoca', 'status' => 'published']);

        \Livewire\Volt\Volt::test('properties.index')
            ->set('city', 'Brașov')
            ->assertSee('Brașov Loft')
            ->assertDontSee('Cluj Apt');
    }

    public function test_properties_index_filters_by_property_type(): void
    {
        $host = Host::factory()->create();
        Property::factory()->for($host)->create(['title' => 'Studio One', 'property_type' => 'studio', 'status' => 'published']);
        Property::factory()->for($host)->create(['title' => 'Vila Mare', 'property_type' => 'villa', 'status' => 'published']);

        \Livewire\Volt\Volt::test('properties.index')
            ->set('propertyType', 'studio')
            ->assertSee('Studio One')
            ->assertDontSee('Vila Mare');
    }

    public function test_property_show_is_publicly_accessible(): void
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create([
            'title' => 'Casa de la Mare',
            'slug' => 'casa-de-la-mare',
            'description' => 'Vacanta perfecta la malul marii.',
            'status' => 'published',
        ]);

        $this->get('/properties/'.$property->slug)
            ->assertOk()
            ->assertSee('Casa de la Mare')
            ->assertSee('Vacanta perfecta la malul marii.');
    }

    public function test_property_show_returns_404_for_draft(): void
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create([
            'slug' => 'casa-draft',
            'status' => 'draft',
        ]);

        $this->get('/properties/'.$property->slug)->assertNotFound();
    }
}
