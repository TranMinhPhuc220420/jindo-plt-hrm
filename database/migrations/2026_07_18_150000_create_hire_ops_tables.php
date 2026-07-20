<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('owner_type', 32);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('category', 32)->default('other');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'owner_type', 'owner_id'], 'idx_documents_company_owner');
            $table->index(['company_id', 'category'], 'idx_documents_company_category');
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('category', 64)->nullable();
            $table->string('status', 32)->default('available');
            $table->string('serial_number', 191)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uq_assets_company_code');
            $table->index(['company_id', 'status'], 'idx_assets_company_status');
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status', 32)->default('active');
            $table->date('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('returned_at')->nullable();
            $table->string('return_condition', 32)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status'], 'idx_asset_assignments_asset_status');
            $table->index(['company_id', 'employee_id'], 'idx_asset_assignments_company_employee');
        });

        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('description');
            $table->string('status', 32)->default('scheduled');
            $table->decimal('cost', 14, 2)->nullable();
            $table->date('scheduled_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status'], 'idx_asset_maintenances_asset_status');
        });

        Schema::create('asset_damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->text('description');
            $table->date('reported_at');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('document_ids')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'asset_id'], 'idx_asset_damage_reports_company_asset');
        });

        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code', 64)->nullable();
            $table->string('title');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('headcount')->default(1);
            $table->string('status', 32)->default('open');
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uq_job_openings_company_code');
            $table->index(['company_id', 'status'], 'idx_job_openings_company_status');
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email', 191)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('stage', 32)->default('applied');
            $table->string('source', 64)->nullable();
            $table->foreignId('resume_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'job_opening_id', 'stage'], 'idx_candidates_company_job_stage');
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('mode', 32)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'candidate_id'], 'idx_interviews_company_candidate');
        });

        Schema::create('candidate_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('interview_id')->constrained('interviews')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('recommendation', 32)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'candidate_id'], 'idx_candidate_evaluations_company_candidate');
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->decimal('salary_amount', 14, 2)->nullable();
            $table->string('currency', 8)->default('VND');
            $table->date('start_date')->nullable();
            $table->date('probation_ends_on')->nullable();
            $table->string('status', 32)->default('draft');
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'candidate_id', 'status'], 'idx_offers_company_candidate_status');
        });

        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name'], 'uq_onboarding_templates_company_name');
        });

        Schema::create('onboarding_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('onboarding_template_id')->constrained('onboarding_templates')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->string('assignee_type', 32)->default('hr');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['onboarding_template_id', 'sort_order'], 'idx_onboarding_template_items_template_sort');
        });

        Schema::create('onboarding_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
            $table->foreignId('onboarding_template_id')->nullable()->constrained('onboarding_templates')->nullOnDelete();
            $table->string('status', 32)->default('in_progress');
            $table->date('probation_ends_on')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'idx_onboarding_cases_company_status');
            $table->index(['company_id', 'employee_id'], 'idx_onboarding_cases_company_employee');
        });

        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('onboarding_case_id')->constrained('onboarding_cases')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->string('assignee_type', 32)->default('hr');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['onboarding_case_id', 'status'], 'idx_onboarding_tasks_case_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
        Schema::dropIfExists('onboarding_cases');
        Schema::dropIfExists('onboarding_template_items');
        Schema::dropIfExists('onboarding_templates');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('candidate_evaluations');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_openings');
        Schema::dropIfExists('asset_damage_reports');
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('documents');
    }
};
