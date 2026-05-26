<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HostDashboardCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actAsHost(): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');
        $host = Host::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return [$user, $host];
    }

    public function test_guest_must_login_to_access_host_dashboard(): void
    {
        $this->get(route('host.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_non_host_user_cannot_access_host_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $this->actingAs($user);

        $this->get(route('host.dashboard'))
            ->assertForbidden();
    }

    public function test_host_sees_only_their_properties_on_dashboard(): void
    {
        [, $host] = $this->actAsHost();
        Property::factory()->for($host)->create(['title' => 'Apartament Centru']);

        // alt host cu altă proprietate
        $otherHost = Host::factory()->create();
        Property::factory()->for($otherHost)->create(['title' => 'Vilă altcuiva']);

        $this->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Apartament Centru')
            ->assertDontSee('Vilă altcuiva');
    }

    public function test_host_dashboard_shows_empty_state_when_no_properties(): void
    {
        $this->actAsHost();

        $this->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Nu ai încă nicio proprietate');
    }

    public function test_host_can_create_property(): void
    {
        [$user, $host] = $this->actAsHost();

        Volt::test('host.properties.create')
            ->set('title', 'Garsoniera Lipscani')
            ->set('description', 'Frumoasa')
            ->set('property_type', 'studio')
            ->set('address', 'Str. Lipscani 10')
            ->set('city', 'București')
            ->set('county', 'Bucuresti')
            ->set('country', 'RO')
            ->set('postal_code', '030001')
            ->set('base_price', 180.0)
            ->set('cleaning_fee', 40.0)
            ->set('max_guests', 2)
            ->set('bedrooms', 0)
            ->set('beds', 1)
            ->set('bathrooms', 1)
            ->set('min_nights', 1)
            ->set('max_nights', 14)
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $property = Property::query()->where('host_id', $host->id)->first();
        $this->assertNotNull($property);
        $this->assertSame('Garsoniera Lipscani', $property->title);
        $this->assertSame('studio', $property->property_type);
        $this->assertSame('draft', $property->status);
        $this->assertEquals(180.0, (float) $property->base_price);
    }

    public function test_create_property_validates_required_fields(): void
    {
        $this->actAsHost();

        Volt::test('host.properties.create')
            ->set('title', '')
            ->set('address', '')
            ->set('city', '')
            ->call('save')
            ->assertHasErrors(['title', 'address', 'city']);

        $this->assertSame(0, Property::query()->count());
    }

    public function test_host_can_edit_their_property(): void
    {
        [, $host] = $this->actAsHost();
        $property = Property::factory()->for($host)->create([
            'title' => 'Vechi',
            'base_price' => 100,
            'status' => 'draft',
        ]);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('title', 'Nou')
            ->set('base_price', 250.0)
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $property->refresh();
        $this->assertSame('Nou', $property->title);
        $this->assertEquals(250.0, (float) $property->base_price);
        $this->assertSame('published', $property->status);
    }

    public function test_host_cannot_edit_another_hosts_property(): void
    {
        $this->actAsHost();
        $otherHost = Host::factory()->create();
        $foreign = Property::factory()->for($otherHost)->create();

        $this->get(route('host.properties.edit', $foreign))
            ->assertForbidden();
    }

    public function test_host_can_delete_their_property(): void
    {
        [, $host] = $this->actAsHost();
        $property = Property::factory()->for($host)->create();

        Volt::test('host.dashboard')
            ->call('delete', $property->id)
            ->assertHasNoErrors();

        $this->assertNull(Property::query()->find($property->id));
    }

    public function test_host_cannot_delete_another_hosts_property(): void
    {
        $this->actAsHost();
        $otherHost = Host::factory()->create();
        $foreign = Property::factory()->for($otherHost)->create();

        try {
            Volt::test('host.dashboard')
                ->call('delete', $foreign->id);
            $this->fail('Expected ModelNotFoundException');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // expected
        }

        $this->assertNotNull(Property::query()->find($foreign->id));
    }
}
