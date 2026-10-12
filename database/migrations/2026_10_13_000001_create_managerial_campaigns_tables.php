<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('managerial_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('district_name', 100);
            $table->string('specialization', 100)->nullable();
            $table->unsignedTinyInteger('duration_months')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 24)->default('pending_approval');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['district_name', 'status'], 'idx_camp_dist_status');
            $table->index(['start_date', 'end_date'], 'idx_camp_dates');
            $table->index(['created_by', 'status'], 'idx_camp_creator_status');
        });

        Schema::create('campaign_products', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained('managerial_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['campaign_id', 'product_id'], 'pk_campaign_products');
        });

        Schema::create('campaign_doctors', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained('managerial_campaigns')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('master_customers')->cascadeOnDelete();
            $table->unsignedTinyInteger('target_visits')->default(1);
            $table->primary(['campaign_id', 'doctor_id'], 'pk_campaign_doctors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_doctors');
        Schema::dropIfExists('campaign_products');
        Schema::dropIfExists('managerial_campaigns');
    }
};
