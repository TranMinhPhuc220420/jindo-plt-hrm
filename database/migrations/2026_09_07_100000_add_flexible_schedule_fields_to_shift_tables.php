<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_generated')->default(false)->after('is_active');
            $table->index(['company_id', 'is_generated'], 'idx_shifts_company_id_is_generated');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->string('source', 16)->default('recurring')->after('weekdays');
            $table->index(
                ['company_id', 'employee_id', 'source', 'start_date'],
                'idx_shift_assignments_company_employee_source_start',
            );
        });
    }

    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_shift_assignments_company_employee_source_start');
            $table->dropColumn('source');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_shifts_company_id_is_generated');
            $table->dropColumn('is_generated');
        });
    }
};
