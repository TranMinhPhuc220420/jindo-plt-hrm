<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('unit_default', 16)->default('day');
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_balance')->default(true);
            $table->boolean('allows_negative')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uq_leave_types_company_code');
            $table->index(['company_id', 'is_active'], 'idx_leave_types_company_active');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->string('period_key', 16);
            $table->decimal('entitled', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['employee_id', 'leave_type_id', 'period_key'],
                'uq_leave_balances_employee_type_period',
            );
            $table->index(['company_id', 'employee_id'], 'idx_leave_balances_company_employee');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->string('unit', 16)->default('day');
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_period', 8)->nullable();
            $table->decimal('quantity', 8, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'start_date'], 'idx_leave_requests_company_status_start');
            $table->index(['employee_id', 'status', 'start_date'], 'idx_leave_requests_employee_status_start');
            $table->index(['leave_type_id', 'employee_id'], 'idx_leave_requests_type_employee');
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->date('date');
            $table->string('name');
            $table->timestamps();

            $table->unique(['company_id', 'date'], 'uq_holidays_company_date');
        });

        Schema::create('weekend_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->json('weekend_days');
            $table->timestamps();

            $table->unique(['company_id'], 'uq_weekend_rules_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekend_rules');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
