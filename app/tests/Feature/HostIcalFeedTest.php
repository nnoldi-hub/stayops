<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\IcalFeed;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HostIcalFeedTest extends TestCase
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

    // ── Access ───────────────────────────────────────────────────────────────

    public function test_host_can_view_ical_page(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        Volt::test('host.properties.ical', ['property' => $property])
            ->assertOk();
    }

    public function test_another_host_cannot_access_ical_page(): void
    {
        // Create property belonging to host1
        $user1 = User::factory()->create([
            'two_factor_secret'       => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user1->assignRole('Host');
        $host1    = Host::factory()->create(['user_id' => $user1->id]);
        $property = $this->makeProperty($host1);

        // Switch to host2
        $this->actAsHost();

        Volt::test('host.properties.ical', ['property' => $property])
            ->assertForbidden();
    }

    // ── Add feed ─────────────────────────────────────────────────────────────

    public function test_host_can_add_ical_feed(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        Volt::test('host.properties.ical', ['property' => $property])
            ->set('newUrl', 'https://www.airbnb.com/calendar/ical/123.ics')
            ->set('newSourceName', 'airbnb')
            ->call('addFeed');

        $this->assertDatabaseHas('ical_feeds', [
            'property_id' => $property->id,
            'direction'   => 'in',
            'source_name' => 'airbnb',
            'url'         => 'https://www.airbnb.com/calendar/ical/123.ics',
        ]);
    }

    public function test_add_feed_rejects_invalid_url(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        Volt::test('host.properties.ical', ['property' => $property])
            ->set('newUrl', 'not-a-url')
            ->call('addFeed')
            ->assertHasErrors(['newUrl']);

        $this->assertDatabaseCount('ical_feeds', 0);
    }

    // ── Delete feed ──────────────────────────────────────────────────────────

    public function test_host_can_delete_feed(): void
    {
        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $feed = IcalFeed::create([
            'property_id' => $property->id,
            'direction'   => 'in',
            'source_name' => 'airbnb',
            'url'         => 'https://example.com/ical.ics',
            'is_active'   => true,
        ]);

        Volt::test('host.properties.ical', ['property' => $property])
            ->call('deleteFeed', $feed->id);

        $this->assertDatabaseMissing('ical_feeds', ['id' => $feed->id]);
    }

    // ── Sync feed ────────────────────────────────────────────────────────────

    public function test_host_can_sync_feed_and_booking_is_created(): void
    {
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//EN',
            'BEGIN:VEVENT',
            'UID:sync-test-001@airbnb.com',
            'DTSTART;VALUE=DATE:20240710',
            'DTEND;VALUE=DATE:20240715',
            'SUMMARY:Jane Smith',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        Http::fake(['*' => Http::response($ics, 200)]);

        [, $host] = $this->actAsHost();
        $property = $this->makeProperty($host);

        $feed = IcalFeed::create([
            'property_id' => $property->id,
            'direction'   => 'in',
            'source_name' => 'airbnb',
            'url'         => 'https://example.com/ical.ics',
            'is_active'   => true,
        ]);

        Volt::test('host.properties.ical', ['property' => $property])
            ->call('syncFeed', $feed->id);

        $this->assertDatabaseHas('bookings', [
            'property_id' => $property->id,
            'source'      => 'ical',
            'status'      => 'confirmed',
            'guest_name'  => 'Jane Smith',
        ]);
    }
}
