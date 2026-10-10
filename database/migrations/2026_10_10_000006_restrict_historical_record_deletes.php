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
        Schema::table('call_list_doctors', function (Blueprint $table) {
            $table->dropForeign(['call_list_id']);
            $table->foreign('call_list_id')->references('id')->on('call_lists')->restrictOnDelete();
        });

        Schema::table('call_list_approval_logs', function (Blueprint $table) {
            $table->dropForeign(['call_list_id']);
            $table->foreign('call_list_id')->references('id')->on('call_lists')->restrictOnDelete();
        });

        Schema::table('call_actual_products', function (Blueprint $table) {
            $table->dropForeign(['call_actual_id']);
            $table->foreign('call_actual_id')->references('id')->on('call_actuals')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_list_doctors', function (Blueprint $table) {
            $table->dropForeign(['call_list_id']);
            $table->foreign('call_list_id')->references('id')->on('call_lists')->cascadeOnDelete();
        });

        Schema::table('call_list_approval_logs', function (Blueprint $table) {
            $table->dropForeign(['call_list_id']);
            $table->foreign('call_list_id')->references('id')->on('call_lists')->cascadeOnDelete();
        });

        Schema::table('call_actual_products', function (Blueprint $table) {
            $table->dropForeign(['call_actual_id']);
            $table->foreign('call_actual_id')->references('id')->on('call_actuals')->cascadeOnDelete();
        });
    }
};
