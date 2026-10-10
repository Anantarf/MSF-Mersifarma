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
        Schema::create('call_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->date('period_month');
            $table->string('status', 24)->default('draft');
            $table->foreignId('assigned_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'period_month'], 'uq_call_list_owner_period');
            $table->unique(['id', 'owner_id'], 'uq_call_list_id_owner');
            $table->index(['assigned_approver_id', 'status'], 'idx_call_list_approver_status');
            $table->index(['owner_id', 'period_month', 'status'], 'idx_call_list_owner_period_status');
        });

        Schema::create('call_list_doctors', function (Blueprint $table) {
            $table->foreignId('call_list_id')->constrained('call_lists')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('master_customers')->restrictOnDelete();
            $table->primary(['call_list_id', 'doctor_id'], 'pk_call_list_doctors');
        });

        Schema::create('call_list_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_list_id')->constrained('call_lists')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event', 32);
            $table->string('event_origin', 24)->default('user_action');
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['call_list_id', 'created_at'], 'idx_approval_log_list_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_list_approval_logs');
        Schema::dropIfExists('call_list_doctors');
        Schema::dropIfExists('call_lists');
    }
};
