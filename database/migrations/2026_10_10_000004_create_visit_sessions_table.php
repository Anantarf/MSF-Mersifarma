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
        Schema::create('visit_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('call_plans')->nullOnDelete();
            $table->foreignId('doctor_id')->constrained('master_customers')->restrictOnDelete();
            $table->date('visit_date');
            $table->dateTime('check_in_at', 6);
            $table->dateTime('check_out_at', 6)->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->json('draft_payload')->nullable();
            $table->unsignedInteger('draft_revision')->default(0);
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'mr_id', 'doctor_id', 'visit_date'], 'uq_session_id_mr_doc_date');
            $table->index(['mr_id', 'status'], 'idx_visit_sessions_mr_status');
        });

        DB::statement("
            ALTER TABLE visit_sessions
            ADD COLUMN active_doctor_id BIGINT UNSIGNED
              GENERATED ALWAYS AS (
                CASE WHEN status='in_progress' THEN doctor_id ELSE NULL END
              ) STORED,
            ADD COLUMN active_plan_id BIGINT UNSIGNED
              GENERATED ALWAYS AS (
                CASE WHEN status='in_progress' THEN plan_id ELSE NULL END
              ) STORED,
            ADD UNIQUE KEY uq_session_active_day (active_doctor_id, visit_date),
            ADD UNIQUE KEY uq_session_active_plan (active_plan_id)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_sessions');
    }
};
