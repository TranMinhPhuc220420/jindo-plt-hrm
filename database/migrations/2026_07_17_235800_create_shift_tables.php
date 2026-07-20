<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->string('kind', 32)->default('standard');
            $table->boolean('is_night')->default(false);
            $table->boolean('is_flexible')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'uq_shifts_company_id_code');
            $table->index(['company_id'], 'idx_shifts_company_id');
        });

        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['company_id', 'employee_id', 'start_date'],
                'idx_shift_assignments_company_employee_start',
            );
            $table->index(['shift_id'], 'idx_shift_assignments_shift_id');
        });

        Schema::create('overtime_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('applies_after_minutes')->default(0);
            $table->boolean('allow_before_shift')->default(false);
            $table->boolean('night_ot_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uq_overtime_rules_company_id_code');
            $table->index(['company_id'], 'idx_overtime_rules_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
