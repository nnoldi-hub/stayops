<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable(); // for external bookings
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('guests_count')->default(1);
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->decimal('nightly_rate', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('host_payout', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 3)->default('RON');
            $table->enum('status', [
                'pending', 'confirmed', 'checked_in', 'checked_out',
                'cancelled', 'refunded', 'no_show', 'disputed',
            ])->default('pending')->index();
            $table->enum('source', ['direct', 'airbnb', 'booking', 'vrbo', 'ical', 'manual'])->default('direct')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'failed'])->default('pending');
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'check_in', 'check_out']);
            $table->index(['property_id', 'status']);
        });

        Schema::create('booking_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('from_state', 30)->nullable();
            $table->string('to_state', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('transitioned_at')->useCurrent();

            $table->index(['booking_id', 'transitioned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_state_transitions');
        Schema::dropIfExists('bookings');
    }
};
