<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ical_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->string('source_name')->nullable(); // airbnb, booking, vrbo, custom
            $table->string('url', 1024)->nullable(); // for direction=in
            $table->string('export_token', 64)->nullable()->unique(); // for direction=out
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'direction']);
        });

        Schema::create('ical_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained('ical_feeds')->cascadeOnDelete();
            $table->string('uid');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['feed_id', 'uid']);
            $table->index(['feed_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ical_events');
        Schema::dropIfExists('ical_feeds');
    }
};
