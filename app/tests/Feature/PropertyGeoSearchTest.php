<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Host;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PropertyGeoSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makePropertyAt(string $title, float $lat, float $lng): Property
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create([
            'title' => $title,
            'status' => 'published',
        ]);

        DB::statement(
            'UPDATE properties SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$lng, $lat, $property->id]
        );

        return $property;
    }

    public function test_geo_filter_returns_only_properties_within_radius(): void
    {
        // București center
        $near = $this->makePropertyAt('Aproape Centru', 44.4268, 26.1025);
        // Cluj-Napoca (~325 km away)
        $far = $this->makePropertyAt('Departe Cluj', 46.7712, 23.6236);

        Volt::test('properties.index')
            ->set('lat', 44.4268)
            ->set('lng', 26.1025)
            ->set('radiusKm', 50)
            ->assertSee($near->title)
            ->assertDontSee($far->title);
    }

    public function test_geo_filter_with_large_radius_returns_both(): void
    {
        $near = $this->makePropertyAt('Aproape Centru', 44.4268, 26.1025);
        $far = $this->makePropertyAt('Departe Cluj', 46.7712, 23.6236);

        Volt::test('properties.index')
            ->set('lat', 44.4268)
            ->set('lng', 26.1025)
            ->set('radiusKm', 500)
            ->assertSee($near->title)
            ->assertSee($far->title);
    }

    public function test_amenity_filter_returns_only_properties_with_amenity(): void
    {
        $wifi = Amenity::create([
            'slug' => 'wifi',
            'name_ro' => 'Wi-Fi',
            'name_en' => 'Wi-Fi',
            'category' => 'basic',
            'sort_order' => 1,
        ]);
        $pool = Amenity::create([
            'slug' => 'pool',
            'name_ro' => 'Piscină',
            'name_en' => 'Pool',
            'category' => 'outdoor',
            'sort_order' => 2,
        ]);

        $host = Host::factory()->create();
        $withWifi = Property::factory()->for($host)->create([
            'title' => 'Are Wifi Aici',
            'status' => 'published',
        ]);
        $withWifi->amenities()->attach($wifi->id);

        $withoutWifi = Property::factory()->for($host)->create([
            'title' => 'Fara Wifi Aici',
            'status' => 'published',
        ]);
        $withoutWifi->amenities()->attach($pool->id);

        Volt::test('properties.index')
            ->set('amenities', [$wifi->id])
            ->assertSee('Are Wifi Aici')
            ->assertDontSee('Fara Wifi Aici');
    }

    public function test_amenity_filter_with_multiple_requires_all(): void
    {
        $wifi = Amenity::create([
            'slug' => 'wifi',
            'name_ro' => 'Wi-Fi',
            'name_en' => 'Wi-Fi',
            'category' => 'basic',
            'sort_order' => 1,
        ]);
        $parking = Amenity::create([
            'slug' => 'parking',
            'name_ro' => 'Parcare',
            'name_en' => 'Parking',
            'category' => 'basic',
            'sort_order' => 2,
        ]);

        $host = Host::factory()->create();
        $both = Property::factory()->for($host)->create([
            'title' => 'Are Ambele Aici',
            'status' => 'published',
        ]);
        $both->amenities()->attach([$wifi->id, $parking->id]);

        $onlyWifi = Property::factory()->for($host)->create([
            'title' => 'Doar Wifi Aici',
            'status' => 'published',
        ]);
        $onlyWifi->amenities()->attach($wifi->id);

        Volt::test('properties.index')
            ->set('amenities', [$wifi->id, $parking->id])
            ->assertSee('Are Ambele Aici')
            ->assertDontSee('Doar Wifi Aici');
    }
}
