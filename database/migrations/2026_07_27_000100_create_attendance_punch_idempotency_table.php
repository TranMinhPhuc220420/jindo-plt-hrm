<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_punch_idempotency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('idempotency_key', 64);
            $table->string('punch_type', 16);
            $table->string('request_fingerprint', 64);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->foreignId('attendance_record_id')
                ->nullable()
                ->constrained('attendance_records')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['company_id', 'employee_id', 'idempotency_key'],
                'uq_attendance_punch_idempotency_key',
            );
            $table->index(
                ['created_at'],
                'idx_attendance_punch_idempotency_created',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punch_idempotency');
    }
};
