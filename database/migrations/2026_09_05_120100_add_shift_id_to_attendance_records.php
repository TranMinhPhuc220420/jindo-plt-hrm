<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('shift_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('shifts')
                ->restrictOnDelete();
        });

        $this->backfillShiftIds();

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique('uq_attendance_records_company_employee_date');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'employee_id', 'work_date', 'shift_id'],
                'uq_attendance_records_company_employee_date_shift',
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique('uq_attendance_records_company_employee_date_shift');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'employee_id', 'work_date'],
                'uq_attendance_records_company_employee_date',
            );
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });
    }

    private function backfillShiftIds(): void
    {
        $records = DB::table('attendance_records')
            ->whereNull('shift_id')
            ->orderBy('id')
            ->get(['id', 'company_id', 'employee_id', 'work_date']);

        foreach ($records as $record) {
            $workDate = $record->work_date;
            $assignment = DB::table('shift_assignments')
                ->where('company_id', $record->company_id)
                ->where('employee_id', $record->employee_id)
                ->whereNull('deleted_at')
                ->where('start_date', '<=', $workDate)
                ->where(function ($q) use ($workDate): void {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $workDate);
                })
                ->orderBy('start_date')
                ->first();

            if ($assignment === null) {
                continue;
            }

            DB::table('attendance_records')
                ->where('id', $record->id)
                ->update(['shift_id' => $assignment->shift_id]);
        }
    }
};
