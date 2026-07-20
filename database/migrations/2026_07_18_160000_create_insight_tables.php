<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
            $table->index(['company_id', 'type'], 'idx_notifications_company_type');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('email')->default(true);
            $table->boolean('push')->default(false);
            $table->boolean('system')->default(true);
            $table->json('categories')->nullable();
            $table->timestamps();
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('report', 64);
            $table->string('format', 16)->default('csv');
            $table->json('filters')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id'], 'idx_report_exports_company_user');
            $table->index(['company_id', 'status'], 'idx_report_exports_company_status');
        });

        Schema::create('performance_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('framework', 16)->default('goal');
            $table->string('status', 16)->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('participant_employee_ids')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'idx_review_cycles_company_status');
        });

        Schema::create('performance_cycle_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('review_cycle_id')->constrained('performance_review_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['review_cycle_id', 'employee_id'], 'uq_cycle_participant');
            $table->index(['company_id', 'employee_id'], 'idx_cycle_participants_company_employee');
        });

        Schema::create('performance_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('review_cycle_id')->nullable()->constrained('performance_review_cycles')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 16)->default('goal');
            $table->string('metric')->nullable();
            $table->string('target')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'employee_id'], 'idx_goals_company_employee');
            $table->index(['company_id', 'review_cycle_id'], 'idx_goals_company_cycle');
        });

        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('review_cycle_id')->constrained('performance_review_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('overall_score', 3, 2)->default(0);
            $table->text('summary')->nullable();
            $table->json('ratings')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['review_cycle_id', 'employee_id'], 'uq_evaluation_cycle_employee');
            $table->index(['company_id', 'employee_id'], 'idx_evaluations_company_employee');
        });

        Schema::create('performance_promotion_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('review_cycle_id')->nullable()->constrained('performance_review_cycles')->nullOnDelete();
            $table->foreignId('evaluation_id')->nullable()->constrained('performance_evaluations')->nullOnDelete();
            $table->decimal('overall_score', 3, 2)->default(0);
            $table->string('status', 16)->default('suggested');
            $table->text('note')->nullable();
            $table->timestamp('suggested_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'review_cycle_id'], 'uq_promotion_employee_cycle');
            $table->index(['company_id', 'status'], 'idx_promotion_company_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_promotion_suggestions');
        Schema::dropIfExists('performance_evaluations');
        Schema::dropIfExists('performance_goals');
        Schema::dropIfExists('performance_cycle_participants');
        Schema::dropIfExists('performance_review_cycles');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
