<?php

namespace Tests\Unit;

use App\Models\Booking;
use PHPUnit\Framework\TestCase;

class BookingStateMachineTest extends TestCase
{
    public function test_pending_can_transition_to_confirmed_or_cancelled(): void
    {
        $booking = new Booking(['status' => 'pending']);
        $booking->status = 'pending';

        $this->assertTrue($booking->canTransitionTo('confirmed'));
        $this->assertTrue($booking->canTransitionTo('cancelled'));
        $this->assertFalse($booking->canTransitionTo('checked_in'));
        $this->assertFalse($booking->canTransitionTo('refunded'));
    }

    public function test_confirmed_can_transition_to_checked_in_cancelled_no_show(): void
    {
        $booking = new Booking();
        $booking->status = 'confirmed';

        $this->assertTrue($booking->canTransitionTo('checked_in'));
        $this->assertTrue($booking->canTransitionTo('cancelled'));
        $this->assertTrue($booking->canTransitionTo('no_show'));
        $this->assertFalse($booking->canTransitionTo('checked_out'));
        $this->assertFalse($booking->canTransitionTo('refunded'));
    }

    public function test_checked_in_can_transition_to_checked_out_or_disputed(): void
    {
        $booking = new Booking();
        $booking->status = 'checked_in';

        $this->assertTrue($booking->canTransitionTo('checked_out'));
        $this->assertTrue($booking->canTransitionTo('disputed'));
        $this->assertFalse($booking->canTransitionTo('pending'));
        $this->assertFalse($booking->canTransitionTo('confirmed'));
    }

    public function test_refunded_is_terminal(): void
    {
        $booking = new Booking();
        $booking->status = 'refunded';

        foreach (Booking::STATUSES as $status) {
            $this->assertFalse(
                $booking->canTransitionTo($status),
                "Refunded should not transition to {$status}"
            );
        }
    }

    public function test_all_statuses_appear_in_transition_map(): void
    {
        foreach (Booking::STATUSES as $status) {
            $this->assertArrayHasKey(
                $status,
                Booking::ALLOWED_TRANSITIONS,
                "Status {$status} missing from ALLOWED_TRANSITIONS"
            );
        }
    }

    public function test_all_transition_targets_are_valid_statuses(): void
    {
        foreach (Booking::ALLOWED_TRANSITIONS as $from => $targets) {
            foreach ($targets as $target) {
                $this->assertContains(
                    $target,
                    Booking::STATUSES,
                    "Transition {$from} -> {$target} targets an unknown status"
                );
            }
        }
    }
}
