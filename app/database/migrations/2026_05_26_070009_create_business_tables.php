<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            // polymorphic target: property, host, provider, guest
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->unsignedTinyInteger('cleanliness')->nullable();
            $table->unsignedTinyInteger('communication')->nullable();
            $table->unsignedTinyInteger('accuracy')->nullable();
            $table->unsignedTinyInteger('value')->nullable();
            $table->text('comment')->nullable();
            $table->text('host_response')->nullable();
            $table->timestamp('host_responded_at')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ro');
            $table->string('name_en');
            $table->text('description_ro')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('RON');
            $table->enum('interval', ['month', 'year']);
            $table->json('features')->nullable();
            $table->unsignedInteger('max_properties')->nullable();
            $table->decimal('platform_fee_pct', 5, 2)->default(0);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('currency', 3)->default('RON');
            $table->enum('status', ['pending', 'collected', 'refunded', 'waived'])->default('pending')->index();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_transfer_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('RON');
            $table->enum('status', ['pending', 'in_transit', 'paid', 'failed', 'reversed'])->default('pending')->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('booking_ids')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // either booking or task
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opener_id')->constrained('users')->cascadeOnDelete();
            $table->enum('category', ['payment', 'damage', 'service_quality', 'cancellation', 'other']);
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'investigating', 'awaiting_response', 'resolved', 'closed', 'escalated'])
                ->default('open')->index();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // admin-only notes
            $table->timestamps();
        });

        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            $table->index(['admin_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('reviews');
    }
};
