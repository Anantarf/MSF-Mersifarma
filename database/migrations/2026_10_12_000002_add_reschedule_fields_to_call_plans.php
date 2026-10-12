<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_plans', function (Blueprint $table) {
            $table->text('reschedule_reason')->nullable()->after('cancelled_at');
            $table->dateTime('rescheduled_at')->nullable()->after('reschedule_reason');
        });
    }

    public function down(): void
    {
        Schema::table('call_plans', function (Blueprint $table) {
            $table->dropColumn(['reschedule_reason', 'rescheduled_at']);
        });
    }
};
