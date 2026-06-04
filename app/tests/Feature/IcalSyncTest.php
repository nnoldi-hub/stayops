<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\IcalFeed;
use App\Models\Property;
use App\Services\IcalSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IcalSyncTest extends TestCase
{
    use RefreshDatabase;

    // ── Sample iCal content ──────────────────────────────────────────────────

    private function sampleIcs(string $uid = 'test-001@airbnb.com'): string
    {
        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//Test//EN',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            'DTSTART;VALUE=DATE:20240610',
            'DTEND;VALUE=DATE:20240615',
            'SUMMARY:John Doe',
            'DESCRIPTION:Booking via Airbnb',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }

    private function makeProperty(): Property
    {
        $host = Host::factory()->create();
        return Property::factory()->for($host)->create(['status' => 'published']);
    }

    private function makeFeed(Property $property, array $attrs = []): IcalFeed
    {
        return IcalFeed::create(array_merge([
            'property_id' => $property->id,
            'direction'   => 'in',
            'source_name' => 'airbnb',
            'url'         => 'https://example.com/ical.ics',
            'is_active'   => true,
        ], $attrs));
    }

    // ── parse() ──────────────────────────────────────────────────────────────

    public function test_parse_returns_correct_events_from_valid_ics(): void
    {
        $service = new IcalSyncService();
        $events  = $service->parse($this->sampleIcs());

        $this->assertCount(1, $events);
        $this->assertEquals('test-001@airbnb.com', $events[0]['uid']);
        $this->assertEquals('2024-06-10', $events[0]['starts_on']);
        $this->assertEquals('2024-06-15', $events[0]['ends_on']);
        $this->assertEquals('John Doe', $events[0]['summary']);
        $this->assertEquals('Booking via Airbnb', $events[0]['description']);
    }

    public function test_parse_returns_empty_array_for_empty_ics(): void
    {
        $service = new IcalSyncService();
        $this->assertSame([], $service->parse(''));
    }

    public function test_parse_handles_folded_lines(): void
    {
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'UID:fold',
            ' -test@example.com',       // continuation line
            'DTSTART;VALUE=DATE:20240101',
            'DTEND;VALUE=DATE:20240105',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $service = new IcalSyncService();
        $events  = $service->parse($ics);

        $this->assertCount(1, $events);
        $this->assertEquals('fold-test@example.com', $events[0]['uid']);
    }

    public function test_parse_skips_events_missing_required_fields(): void
    {
        // Event without UID
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'DTSTART;VALUE=DATE:20240101',
            'DTEND;VALUE=DATE:20240105',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $service = new IcalSyncService();
        $this->assertSame([], $service->parse($ics));
    }

    // ── sync() ───────────────────────────────────────────────────────────────

    public function test_sync_creates_ical_event_and_booking_for_new_events(): void
    {
        Http::fake(['*' => Http::response($this->sampleIcs(), 200)]);

        $property = $this->makeProperty();
        $feed     = $this->makeFeed($property);

        $service = new IcalSyncService();
        $synced  = $service->sync($feed);

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('ical_events', [
            'feed_id' => $feed->id,
            'uid'     => 'test-001@airbnb.com',
        ]);
        $this->assertDatabaseHas('bookings', [
            'property_id'    => $property->id,
            'source'         => 'ical',
            'status'         => 'confirmed',
            'payment_status' => 'paid',
            'external_id'    => 'test-001@airbnb.com',
        ]);
    }

    public function test_sync_does_not_duplicate_booking_on_second_run(): void
    {
        Http::fake(['*' => Http::response($this->sampleIcs(), 200)]);

        $property = $this->makeProperty();
        $feed     = $this->makeFeed($property);

        $service = new IcalSyncService();
        $service->sync($feed);
        $second = $service->sync($feed);

        $this->assertEquals(0, $second);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('ical_events', 1);
    }

    public function test_sync_updates_feed_last_sync_at_and_events_count(): void
    {
        Http::fake(['*' => Http::response($this->sampleIcs(), 200)]);

        $property = $this->makeProperty();
        $feed     = $this->makeFeed($property);

        (new IcalSyncService())->sync($feed);

        $feed->refresh();
        $this->assertNotNull($feed->last_sync_at);
        $this->assertEquals(1, $feed->events_count);
        $this->assertNull($feed->last_error);
    }

    public function test_sync_records_error_and_rethrows_on_http_failure(): void
    {
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $property = $this->makeProperty();
        $feed     = $this->makeFeed($property);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        try {
            (new IcalSyncService())->sync($feed);
        } finally {
            $this->assertNotNull($feed->fresh()->last_error);
        }
    }

    public function test_sync_returns_zero_for_inactive_feed(): void
    {
        $property = $this->makeProperty();
        $feed     = $this->makeFeed($property, ['is_active' => false]);

        $synced = (new IcalSyncService())->sync($feed);

        $this->assertEquals(0, $synced);
        Http::assertNothingSent();
    }
}
