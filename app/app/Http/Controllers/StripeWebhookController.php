<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, BookingService $bookingService): JsonResponse
    {
        $event = $request->input('type');
        $object = $request->input('data.object', []);

        if ($event === 'payment_intent.succeeded') {
            $paymentIntentId = $object['id'] ?? null;

            if ($paymentIntentId) {
                $booking = Booking::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->first();

                if ($booking) {
                    $booking->payment_status = 'paid';
                    $booking->save();

                    if ($booking->status === 'pending' && $booking->canTransitionTo('confirmed')) {
                        try {
                            $bookingService->confirm($booking);
                        } catch (\Throwable $e) {
                            Log::error('Stripe webhook confirm failed', [
                                'booking_id' => $booking->id,
                                'payment_intent_id' => $paymentIntentId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
