<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->string('punch_type', 16);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy_meters')->nullable();
            $table->string('address', 500);
            $table->string('photo_path', 512);
            $table->string('photo_mime', 64)->nullable();
            $table->unsignedInteger('photo_size')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['attendance_record_id', 'punch_type'],
                'uq_attendance_evidences_record_punch',
            );
            $table->index(
                ['company_id', 'attendance_record_id'],
                'idx_attendance_evidences_company_record',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_evidences');
    }
};
