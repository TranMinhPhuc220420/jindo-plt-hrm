<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_punch_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedBigInteger('shift_id');
            $table->string('kind', 16);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['company_id', 'employee_id', 'work_date', 'shift_id', 'kind'],
                'uq_attendance_punch_reminders',
            );
            $table->index(['company_id', 'work_date'], 'idx_attendance_punch_reminders_company_date');
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('endpoint', 2048);
            $table->char('endpoint_hash', 64);
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 32)->default('aes128gcm');
            $table->timestamps();

            $table->unique('endpoint_hash', 'uq_push_subscriptions_endpoint_hash');
            $table->index('user_id', 'idx_push_subscriptions_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('attendance_punch_reminders');
    }
};
