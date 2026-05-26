<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('property_type', 50)->index(); // apartment, house, villa, studio, room
            $table->string('address');
            $table->string('city')->index();
            $table->string('county', 100)->nullable();
            $table->string('country', 2)->default('RO');
            $table->string('postal_code', 20)->nullable();
            // location POINT added via DB::statement below
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3)->default('RON');
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->unsignedSmallInteger('max_guests')->default(2);
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('beds')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->time('check_in_time')->default('15:00:00');
            $table->time('check_out_time')->default('11:00:00');
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('max_nights')->default(30);
            $table->text('house_rules')->nullable();
            $table->enum('status', ['draft', 'published', 'paused', 'archived'])->default('draft')->index();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city', 'status']);
        });

        // PostGIS geometry column + GIST index
        DB::statement('ALTER TABLE properties ADD COLUMN location geography(POINT, 4326) NULL');
        DB::statement('CREATE INDEX properties_location_gix ON properties USING GIST (location)');

        Schema::create('property_amenities', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['property_id', 'amenity_id']);
        });

        Schema::create('property_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['seasonal', 'weekend', 'last_minute', 'long_stay', 'custom_date']);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('days_of_week')->nullable(); // [0..6]
            $table->enum('modifier_type', ['percentage', 'fixed']);
            $table->decimal('modifier_value', 10, 2);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_pricing_rules');
        Schema::dropIfExists('property_amenities');
        Schema::dropIfExists('properties');
    }
};
