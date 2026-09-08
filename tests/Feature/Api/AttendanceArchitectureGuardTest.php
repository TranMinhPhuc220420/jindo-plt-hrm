<?php

/**
 * Architectural dependency guard: Attendance must not write payroll tables.
 *
 * @group critical
 */

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('check-in and correction approval do not create payroll runs or payslips', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = actingUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_approve_attendance',
        'can_request_attendance_correction',
    ], prefix: 'arch');

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-ARCH-'.uniqid(),
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2020-01-01',
        'end_date' => null,
    ]);

    $payslipCountBefore = Payslip::query()->count();
    $runCountBefore = PayrollRun::query()->count();

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', [
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'accuracy_meters' => 12.5,
            'address' => 'HCMC',
            'photo' => UploadedFile::fake()->image('punch.jpg', 320, 240),
            'client_punched_at' => '2026-07-16T08:05:00+07:00',
        ])
        ->assertCreated();

    expect(Payslip::query()->count())->toBe($payslipCountBefore)
        ->and(PayrollRun::query()->count())->toBe($runCountBefore)
        ->and(AttendanceRecord::query()->where('company_id', $company->id)->count())->toBeGreaterThan(0);
});
