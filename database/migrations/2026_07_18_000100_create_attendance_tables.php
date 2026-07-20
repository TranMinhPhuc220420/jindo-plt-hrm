<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('break_minutes')->default(0);
            $table->string('status', 32)->default('open');
            $table->string('source', 32)->default('manual');
            $table->text('note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'employee_id', 'work_date'],
                'uq_attendance_records_company_employee_date',
            );
            $table->index(
                ['company_id', 'employee_id', 'work_date'],
                'idx_attendance_records_company_employee_date',
            );
            $table->index(
                ['company_id', 'status', 'work_date'],
                'idx_attendance_records_company_status_date',
            );
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->dateTime('proposed_check_in_at')->nullable();
            $table->dateTime('proposed_check_out_at')->nullable();
            $table->text('reason');
            $table->string('status', 32)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'idx_attendance_corrections_company_status');
            $table->index(['attendance_record_id'], 'idx_attendance_corrections_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance_records');
    }
};
