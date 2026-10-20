<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('cui', 32)->nullable()->index();
            $table->string('registration_number', 64)->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country', 2)->default('RO');
            $table->string('vat_number', 32)->nullable();
            $table->enum('kyc_status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending')->index();
            $table->json('kyc_documents')->nullable();
            $table->string('stripe_account_id')->nullable()->index();
            $table->enum('stripe_account_status', ['none', 'pending', 'active', 'restricted'])->default('none');
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosts');
    }
};
