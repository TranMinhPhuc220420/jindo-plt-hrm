<?php

use App\Models\AttendanceEvidence;
use App\Models\AttendancePunchIdempotency;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRule;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function seedAttendanceAuth(): void
{
    seedAuthCatalog();
}

function attendanceUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'att');
}

function linkEmployee(User $user, Company $company, bool $withShift = true): Employee
{
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-ATT-'.uniqid(),
    ]);

    if ($withShift) {
        $shift = Shift::factory()->create([
            'company_id' => $company->id,
        ]);
        ShiftAssignment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'start_date' => '2020-01-01',
            'end_date' => null,
        ]);
    }

    return $employee;
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function attendanceEvidence(array $extra = []): array
{
    return array_merge([
        'latitude' => 10.7769000,
        'longitude' => 106.7009000,
        'accuracy_meters' => 12.5,
        'address' => 'Ho Chi Minh City, Vietnam',
        'photo' => UploadedFile::fake()->image('punch.jpg', 320, 240),
    ], $extra);
}

test('cannot check in without can_check_in_out', function () {
    Company::factory()->create();
    $user = attendanceUser([]);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence())
        ->assertForbidden();
});

test('check-in without evidence is rejected and creates no record', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_EVIDENCE_REQUIRED');

    expect(AttendanceRecord::query()->count())->toBe(0)
        ->and(AttendanceEvidence::query()->count())->toBe(0);
});

test('employee can check in and out with metrics from shift', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
    ]);
    $employee = linkEmployee($user, $company, withShift: false);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-ATT',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    OvertimeRule::factory()->create([
        'company_id' => $company->id,
        'code' => 'STANDARD',
        'applies_after_minutes' => 0,
        'is_active' => true,
    ]);

    $in = $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:15:00+07:00',
        ]));

    $in->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.late_minutes', 15)
        ->assertJsonPath('data.evidences.0.punch_type', 'check_in')
        ->assertJsonPath('data.evidences.0.address', 'Ho Chi Minh City, Vietnam');

    $evidence = AttendanceEvidence::query()->first();
    expect($evidence)->not->toBeNull();
    Storage::disk('local')->assertExists($evidence->photo_path);
    expect(AuditLog::query()->where('action', 'attendance.checked_in')->count())->toBe(1);

    $out = $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:30:00+07:00',
            'address' => 'District 1, Ho Chi Minh City',
        ]));

    $out->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.overtime_minutes', 30)
        ->assertJsonPath('data.evidences.1.punch_type', 'check_out');

    expect(AttendanceEvidence::query()->count())->toBe(2);
});

test('evidence photo endpoint is authorized', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
    ]);
    linkEmployee($user, $company);

    $recordId = $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$recordId}/evidences/check_in/photo")
        ->assertOk();

    $outsider = attendanceUser(['can_view_attendance']);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $outsider->id,
        'code' => 'E-ATT-OUT-'.uniqid(),
    ]);

    // Own-scope viewer without approve/manage cannot view another employee's evidence.
    $this->actingAs($outsider)
        ->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$recordId}/evidences/check_in/photo")
        ->assertStatus(403);

    // Manager / approver can view employee punch photos.
    $manager = attendanceUser([
        'can_view_attendance',
        'can_approve_attendance',
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$recordId}/evidences/check_in/photo")
        ->assertOk();
});

test('check-in rejects an out-of-range latitude', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'latitude' => 95.0,
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_EVIDENCE_REQUIRED')
        ->assertJsonPath('errors.latitude.0', fn ($value) => is_string($value));

    expect(AttendanceRecord::query()->count())->toBe(0);
});

test('check-in rejects a non-image photo upload', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'photo' => UploadedFile::fake()->create('note.pdf', 100, 'application/pdf'),
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_EVIDENCE_REQUIRED');

    expect(AttendanceRecord::query()->count())->toBe(0);
});

test('check-in rejects a photo larger than 5MB', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'photo' => UploadedFile::fake()->image('big.jpg')->size(6000),
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_EVIDENCE_REQUIRED');

    expect(AttendanceRecord::query()->count())->toBe(0);
});

test('check-in requires authentication', function () {
    Company::factory()->create();

    $this->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence())
        ->assertStatus(401);
});

test('evidence photo endpoint requires authentication', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $record = AttendanceRecord::factory()->create(['company_id' => $company->id]);
    AttendanceEvidence::query()->create([
        'company_id' => $company->id,
        'attendance_record_id' => $record->id,
        'punch_type' => AttendanceEvidence::PUNCH_CHECK_IN,
        'latitude' => 10.0,
        'longitude' => 106.0,
        'address' => 'Somewhere',
        'photo_path' => 'attendance/guest/fake.jpg',
        'photo_mime' => 'image/jpeg',
        'photo_size' => 100,
        'captured_at' => now(),
    ]);

    // No actingAs() in this test — the request must be treated as a guest.
    $this->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$record->id}/evidences/check_in/photo")
        ->assertStatus(401);
});

test('evidence photo for a punch type without a stored photo returns 404', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_attendance']);
    linkEmployee($user, $company);

    $recordId = $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence())
        ->assertCreated()
        ->json('data.id');

    // Check-in evidence exists, but the employee has not checked out yet.
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$recordId}/evidences/check_out/photo")
        ->assertStatus(404);
});

test('evidence photo for a record belonging to another company is not found', function () {
    Storage::fake('local');
    // The first company created becomes the active one resolved by CompanyContext.
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $manager = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    linkEmployee($manager, $company);

    $foreignRecord = AttendanceRecord::factory()->create(['company_id' => $otherCompany->id]);
    AttendanceEvidence::query()->create([
        'company_id' => $otherCompany->id,
        'attendance_record_id' => $foreignRecord->id,
        'punch_type' => AttendanceEvidence::PUNCH_CHECK_IN,
        'latitude' => 10.0,
        'longitude' => 106.0,
        'address' => 'Somewhere',
        'photo_path' => 'attendance/foreign/fake.jpg',
        'photo_mime' => 'image/jpeg',
        'photo_size' => 100,
        'captured_at' => now(),
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->get("/api/attendance/records/{$foreignRecord->id}/evidences/check_in/photo")
        ->assertStatus(404);
});

test('double check-in returns ATTENDANCE_ALREADY_CHECKED_IN', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:05:00+07:00',
        ]))
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_ALREADY_CHECKED_IN');
});

test('checking out without an open check-in returns ATTENDANCE_INVALID_TRANSITION', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_INVALID_TRANSITION');
});

test('check-out after midnight attaches to the previous open session', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-27T22:00:00+07:00',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.work_date', '2026-07-27');

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-28T00:57:00+07:00',
        ]))
        ->assertOk()
        ->assertJsonPath('data.work_date', '2026-07-27')
        ->assertJsonPath('data.status', 'pending');

    expect(
        AttendanceRecord::query()
            ->whereDate('work_date', '2026-07-27')
            ->whereNotNull('check_out_at')
            ->exists(),
    )->toBeTrue();
});

test('check-out uses captured_at when worked_at is omitted', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-27T22:00:00+07:00',
        ]))
        ->assertCreated();

    $response = $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'captured_at' => '2026-07-28T00:57:00+07:00',
        ]));

    $response->assertOk()
        ->assertJsonPath('data.work_date', '2026-07-27');

    $checkOutAt = CarbonImmutable::parse((string) $response->json('data.check_out_at'))
        ->timezone('Asia/Ho_Chi_Minh');

    expect($checkOutAt->format('Y-m-d H:i'))->toBe('2026-07-28 00:57');
});

test('correction approve and reject with audit', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $employeeUser = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
    ]);
    $employee = linkEmployee($employeeUser, $company);

    $approver = attendanceUser([
        'can_view_attendance',
        'can_approve_attendance',
    ]);

    $this->actingAs($employeeUser)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T09:00:00+07:00',
        ]))
        ->assertCreated();

    $this->actingAs($employeeUser)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertOk();

    $recordId = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->assertOk()
        ->json('data.0.id');

    $correctionId = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00:00+07:00',
            'proposed_check_out_at' => '2026-07-16T17:00:00+07:00',
            'reason' => 'Forgot earlier punch',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/corrections/{$correctionId}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect(AuditLog::query()->where('action', 'attendance.correction_approved')->count())->toBe(1);

    // Second correction then reject
    $this->actingAs($employeeUser)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-17T08:00:00+07:00',
        ]))
        ->assertCreated();
    $this->actingAs($employeeUser)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-17T17:00:00+07:00',
        ]))
        ->assertOk();

    $record2 = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records?date_from=2026-07-17&date_to=2026-07-17')
        ->json('data.0.id');

    $c2 = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $record2,
            'proposed_check_in_at' => '2026-07-17T08:30:00+07:00',
            'proposed_check_out_at' => '2026-07-17T17:00:00+07:00',
            'reason' => 'Wrong time',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/corrections/{$c2}/reject", [
            'review_note' => 'Not enough evidence',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect($employee->id)->toBeInt();
});

test('locked period blocks check-in and correction', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
        'can_manage_attendance',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertOk();

    $recordId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->json('data.0.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/period/lock', [
            'date_from' => '2026-07-16',
            'date_to' => '2026-07-16',
        ])
        ->assertOk()
        ->assertJsonPath('data.locked_count', 1);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00:00+07:00',
            'proposed_check_out_at' => '2026-07-16T18:00:00+07:00',
            'reason' => 'Too late',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_PERIOD_LOCKED');

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-18T08:00:00+07:00',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/period/lock', [
            'date_from' => '2026-07-18',
            'date_to' => '2026-07-18',
        ])
        ->assertOk();

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-18T17:00:00+07:00',
        ]))
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_PERIOD_LOCKED');
});

test('summary aggregates period minutes', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
    ]);
    $employee = linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertOk();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/summary?employee_id='.$employee->id.'&period_start=2026-07-01&period_end=2026-07-31')
        ->assertOk()
        ->assertJsonPath('data.employee_id', $employee->id)
        ->assertJsonPath('data.days_present', 1);
});

test('AttendanceSummaryService smoke without payroll writes', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'work_date' => '2026-07-16',
        'worked_minutes' => 480,
        'late_minutes' => 10,
        'overtime_minutes' => 20,
        'status' => 'pending',
        'check_in_at' => '2026-07-16 08:00:00',
        'check_out_at' => '2026-07-16 17:00:00',
    ]);

    session(['company_id' => $company->id]);

    $summary = app(AttendanceSummaryService::class)->summarize(
        $user,
        $employee->id,
        '2026-07-01',
        '2026-07-31',
    );

    expect($summary['worked_minutes'])->toBe(480)
        ->and($summary['late_minutes'])->toBe(10)
        ->and($summary['overtime_minutes'])->toBe(20)
        ->and($summary['days_present'])->toBe(1);

    expect(PayrollRun::query()->count())->toBe(0)
        ->and(Payslip::query()->count())->toBe(0);
});

test('record approve path works', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_approve_attendance',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertOk();

    $id = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->json('data.0.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/records/{$id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

test('bulk approve pending records', function () {
    $company = Company::factory()->create();
    $approver = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    linkEmployee($approver, $company);

    $pendingA = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'status' => 'pending',
        'work_date' => '2026-07-16',
    ]);
    $pendingB = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'status' => 'pending',
        'work_date' => '2026-07-17',
    ]);

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/records/bulk-approve', [
            'ids' => [$pendingA->id, $pendingB->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.approved_count', 2)
        ->assertJsonPath('data.approved_ids', [$pendingA->id, $pendingB->id])
        ->assertJsonPath('data.skipped_ids', []);

    expect($pendingA->fresh()->status)->toBe('approved')
        ->and($pendingB->fresh()->status)->toBe('approved')
        ->and(AuditLog::query()->where('action', 'attendance.record_approved')->count())->toBe(2);
});

test('bulk approve skips non-pending and foreign company ids', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $approver = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    linkEmployee($approver, $company);

    $pending = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'status' => 'pending',
        'work_date' => '2026-07-16',
    ]);
    $alreadyApproved = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'status' => 'approved',
        'work_date' => '2026-07-17',
    ]);
    $foreign = AttendanceRecord::factory()->create([
        'company_id' => $otherCompany->id,
        'status' => 'pending',
        'work_date' => '2026-07-18',
    ]);

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/records/bulk-approve', [
            'ids' => [$pending->id, $alreadyApproved->id, $foreign->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.approved_count', 1)
        ->assertJsonPath('data.approved_ids', [$pending->id]);

    $skipped = $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/records/bulk-approve', [
            'ids' => [$alreadyApproved->id, $foreign->id],
        ])
        ->assertStatus(422)
        ->json('error_code');

    expect($skipped)->toBe('ATTENDANCE_INVALID_TRANSITION')
        ->and($pending->fresh()->status)->toBe('approved')
        ->and($alreadyApproved->fresh()->status)->toBe('approved')
        ->and($foreign->fresh()->status)->toBe('pending');
});

test('bulk approve requires permission and non-empty ids', function () {
    $company = Company::factory()->create();
    $viewer = attendanceUser(['can_view_attendance']);
    $approver = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    linkEmployee($viewer, $company);
    linkEmployee($approver, $company);

    $pending = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'status' => 'pending',
    ]);

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/records/bulk-approve', [
            'ids' => [$pending->id],
        ])
        ->assertForbidden();

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/records/bulk-approve', [
            'ids' => [],
        ])
        ->assertStatus(422);
});

test('naive datetime-local correction is interpreted in company timezone', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T09:00:00+07:00',
        ]))
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T18:00:00+07:00',
        ]))
        ->assertOk();

    $recordId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->assertOk()
        ->json('data.0.id');

    $correction = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00',
            'proposed_check_out_at' => '2026-07-16T17:00',
            'reason' => 'Fix to standard hours',
        ])
        ->assertCreated()
        ->json('data');

    expect($correction['proposed_check_in_at'])->toContain('01:00:00')
        ->and($correction['proposed_check_out_at'])->toContain('10:00:00');
});

test('full-day approved leave suppresses late and overtime metrics', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_attendance']);
    $employee = linkEmployee($user, $company, withShift: false);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-LEAVE',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    OvertimeRule::factory()->create([
        'company_id' => $company->id,
        'code' => 'STD-LEAVE',
        'applies_after_minutes' => 0,
        'is_active' => true,
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);
    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'day',
        'start_date' => '2026-07-16',
        'end_date' => '2026-07-16',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:15:00+07:00',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.late_minutes', 0);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:30:00+07:00',
        ]))
        ->assertOk()
        ->assertJsonPath('data.overtime_minutes', 0)
        ->assertJsonPath('data.early_leave_minutes', 0);
});

test('am half-day leave evaluates late against afternoon window', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_attendance']);
    $employee = linkEmployee($user, $company, withShift: false);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-HALF',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 0,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);
    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'half_day',
        'is_half_day' => true,
        'half_day_period' => 'am',
        'start_date' => '2026-07-17',
        'end_date' => '2026-07-17',
        'quantity' => 0.5,
        'status' => 'approved',
    ]);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-17T13:00:00+07:00',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.late_minutes', 30);
});

test('check-in without Idempotency-Key is rejected', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'IDEMPOTENCY_KEY_REQUIRED');

    expect(AttendanceRecord::query()->count())->toBe(0);
});

test('repeated check-in with the same Idempotency-Key replays without a second write', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);
    $key = (string) Str::uuid();
    $shared = [
        'worked_at' => '2026-07-16T08:00:00+07:00',
        'captured_at' => '2026-07-16T08:00:00+07:00',
        'address' => 'Ho Chi Minh City, Vietnam',
        'latitude' => 10.7769000,
        'longitude' => 106.7009000,
    ];

    $first = $this->actingAs($user)
        ->withHeaders(punchHeaders($key))
        ->post('/api/attendance/check-in', attendanceEvidence($shared))
        ->assertCreated();

    $second = $this->actingAs($user)
        ->withHeaders(punchHeaders($key))
        ->post('/api/attendance/check-in', attendanceEvidence($shared))
        ->assertCreated();

    expect($second->json())->toEqual($first->json())
        ->and(AttendanceRecord::query()->count())->toBe(1)
        ->and(AttendanceEvidence::query()->count())->toBe(1)
        ->and(AttendancePunchIdempotency::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'attendance.checked_in')->count())->toBe(1);
});

test('same Idempotency-Key with a different body returns IDEMPOTENCY_KEY_REUSE', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);
    $key = (string) Str::uuid();

    $this->actingAs($user)
        ->withHeaders(punchHeaders($key))
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
            'address' => 'First address',
        ]))
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(punchHeaders($key))
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
            'address' => 'Different address',
        ]))
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'IDEMPOTENCY_KEY_REUSE');

    expect(AttendanceRecord::query()->count())->toBe(1);
});

test('domain failure does not cache an idempotency success response', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);
    $key = (string) Str::uuid();

    $this->actingAs($user)
        ->withHeaders(punchHeaders($key))
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_INVALID_TRANSITION');

    expect(AttendancePunchIdempotency::query()->count())->toBe(0)
        ->and(AttendanceRecord::query()->count())->toBe(0);
});

test('cannot check in without a shift assignment for the work date', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company, withShift: false);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_NO_SHIFT');

    expect(AttendanceRecord::query()->count())->toBe(0);
});

test('two non-overlapping sessions on the same day create two records', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_own_attendance']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-ATT-DUAL-'.uniqid(),
    ]);
    $morning = Shift::factory()->create([
        'company_id' => $company->id,
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'break_minutes' => 0,
    ]);
    $afternoon = Shift::factory()->create([
        'company_id' => $company->id,
        'start_time' => '13:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 0,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $morning->id,
        'start_date' => '2026-08-03',
        'end_date' => '2026-08-03',
        'weekdays' => [1],
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $afternoon->id,
        'start_date' => '2026-08-03',
        'end_date' => '2026-08-03',
        'weekdays' => [1],
    ]);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-08-03T09:00:00+07:00',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.shift_id', $morning->id);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-08-03T11:30:00+07:00',
        ]))
        ->assertOk();

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-08-03T14:00:00+07:00',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.shift_id', $afternoon->id);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-out', attendanceEvidence([
            'worked_at' => '2026-08-03T17:00:00+07:00',
        ]))
        ->assertOk();

    expect(AttendanceRecord::query()->where('employee_id', $employee->id)->count())->toBe(2);

    session(['company_id' => $company->id]);

    $summary = app(AttendanceSummaryService::class)->summarizeForPayroll(
        $employee->id,
        '2026-08-01',
        '2026-08-31',
    );

    expect($summary['days_present'])->toBe(1);
});

test('weekday-masked assignment rejects punch on a scheduled-off day', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-ATT-OFF-'.uniqid(),
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-08-03',
        'end_date' => '2026-08-07',
        'weekdays' => [1, 3, 5],
    ]);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-08-04T08:00:00+07:00',
        ]))
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_NO_SHIFT');
});

test('inactive employee cannot check in', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    $employee = linkEmployee($user, $company);
    $employee->update(['status' => 'suspended']);

    $this->actingAs($user)
        ->withHeaders(punchHeaders())
        ->post('/api/attendance/check-in', attendanceEvidence([
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ]))
        ->assertForbidden()
        ->assertJsonPath('error_code', 'AUTH_ACCOUNT_INACTIVE');

    expect(AttendanceRecord::query()->count())->toBe(0);
});
