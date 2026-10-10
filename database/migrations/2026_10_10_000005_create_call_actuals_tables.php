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
        Schema::create('call_actuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('call_plans')->restrictOnDelete();
            $table->foreignId('visit_session_id')->nullable()->constrained('visit_sessions')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('master_customers')->restrictOnDelete();
            $table->date('visit_date');
            $table->dateTime('check_in_at', 6);
            $table->dateTime('check_out_at', 6);
            $table->string('visit_type', 20); // planned, unplanned, non_target
            $table->text('photo_url');
            $table->text('signature_url');
            $table->text('visit_reason')->nullable();
            $table->text('deviation_reason')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'visit_date'], 'uq_actual_doctor_day');
            $table->unique('plan_id', 'uq_actual_plan');
            $table->unique('visit_session_id', 'uq_actual_session');
            $table->index(['mr_id', 'visit_date'], 'idx_call_actuals_mr_date');
        });

        Schema::create('call_actual_products', function (Blueprint $table) {
            $table->foreignId('call_actual_id')->constrained('call_actuals')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->primary(['call_actual_id', 'product_id'], 'pk_call_actual_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_actual_products');
        Schema::dropIfExists('call_actuals');
    }
};
