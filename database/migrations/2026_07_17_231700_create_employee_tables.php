<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('hr_owner_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->date('hired_at')->nullable();
            $table->string('status', 32)->default('probation');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'uq_employees_company_id_code');
            $table->index(['company_id', 'status'], 'idx_employees_company_id_status');
            $table->index(['company_id', 'department_id'], 'idx_employees_company_id_department_id');
            $table->index('manager_id', 'idx_employees_manager_id');
        });

        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['employee_id'], 'idx_employee_emergency_contacts_employee_id');
        });

        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('school');
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id'], 'idx_employee_educations_employee_id');
        });

        Schema::create('employee_work_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('employer');
            $table->string('title')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['employee_id'], 'idx_employee_work_histories_employee_id');
        });

        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->timestamps();

            $table->index(['employee_id'], 'idx_employee_family_members_employee_id');
        });

        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('contract_number')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id'], 'idx_employee_contracts_employee_id');
        });

        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_name')->nullable();
            $table->string('account_number');
            $table->string('branch_name')->nullable();
            $table->string('swift_code')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('social_insurance_number')->nullable();
            $table->string('health_insurance_number')->nullable();
            $table->string('provider')->nullable();
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->string('tax_code')->nullable();
            $table->string('tax_residency')->nullable();
            $table->unsignedTinyInteger('dependents_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_tax_profiles');
        Schema::dropIfExists('employee_insurances');
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('employee_family_members');
        Schema::dropIfExists('employee_work_histories');
        Schema::dropIfExists('employee_educations');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employees');
    }
};
