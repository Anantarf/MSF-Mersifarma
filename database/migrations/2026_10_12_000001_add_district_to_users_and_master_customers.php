<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('district', 100)->nullable()->after('region_id');
            $table->index(['role', 'district'], 'idx_users_role_district');
        });

        Schema::table('master_customers', function (Blueprint $table) {
            $table->string('district', 100)->nullable()->after('specialization');
            $table->index('district', 'idx_master_customers_district');
        });
    }

    public function down(): void
    {
        Schema::table('master_customers', function (Blueprint $table) {
            $table->dropIndex('idx_master_customers_district');
            $table->dropColumn('district');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_district');
            $table->dropColumn('district');
        });
    }
};
