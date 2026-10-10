<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('call_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_list_id');
            $table->foreignId('mr_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('master_customers')->restrictOnDelete();
            $table->date('plan_date');
            $table->time('planned_time');
            $table->string('status', 20)->default('scheduled');
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            // Candidate key for downstream FKs
            $table->unique(['id', 'mr_id', 'doctor_id'], 'uq_plan_id_mr_doctor');
            $table->index(['mr_id', 'plan_date'], 'idx_call_plans_mr_date');

            // Composite foreign keys
            $table->foreign(['call_list_id', 'mr_id'])
                ->references(['id', 'owner_id'])
                ->on('call_lists')
                ->restrictOnDelete();

            $table->foreign(['call_list_id', 'doctor_id'])
                ->references(['call_list_id', 'doctor_id'])
                ->on('call_list_doctors')
                ->restrictOnDelete();
        });

        // Add generated column active_doctor_id and unique constraint for active plan doctor/date
        DB::statement("
            ALTER TABLE call_plans
            ADD COLUMN active_doctor_id BIGINT UNSIGNED
              GENERATED ALWAYS AS (
                CASE WHEN status IN ('scheduled','completed') THEN doctor_id ELSE NULL END
              ) STORED,
            ADD UNIQUE KEY uq_plan_active_day (active_doctor_id, plan_date)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_plans');
    }
};
