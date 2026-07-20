<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('VND');
            $table->string('strategy', 32)->default('monthly');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from'], 'idx_employee_salaries_employee_effective');
            $table->index(['company_id', 'employee_id'], 'idx_employee_salaries_company_employee');
        });

        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_active'], 'idx_employee_allowances_employee_active');
        });

        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_active'], 'idx_employee_deductions_employee_active');
        });

        Schema::create('employee_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_active'], 'idx_employee_bonuses_employee_active');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('run_type', 32)->default('regular');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['company_id', 'period_start', 'period_end', 'run_type'],
                'uq_payroll_runs_company_period_type',
            );
            $table->index(
                ['company_id', 'period_start', 'period_end'],
                'idx_payroll_runs_company_period',
            );
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->json('components');
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id'], 'uq_payroll_items_run_employee');
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->json('components');
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at'], 'idx_payslips_employee_created');
            $table->unique(['payroll_item_id'], 'uq_payslips_payroll_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_bonuses');
        Schema::dropIfExists('employee_deductions');
        Schema::dropIfExists('employee_allowances');
        Schema::dropIfExists('employee_salaries');
    }
};
