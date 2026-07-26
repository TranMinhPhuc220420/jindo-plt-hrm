<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WeekendRule;
use App\Services\Shift\WorkingCalendarService;

function seedShiftAuth(): void
{
    seedAuthCatalog();
}

function shiftUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'shift');
}

test('cannot list shifts without can_view_shifts', function () {
    Company::factory()->create();
    $user = shiftUser([]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/shifts')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('hr can create list update and delete shifts', function () {
    Company::factory()->create(['code' => 'SHCO']);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
    ]);

    $created = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MORNING',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'kind' => 'standard',
        ]);

    $created->assertCreated()
        ->assertJsonPath('data.code', 'MORNING')
        ->assertJsonPath('data.start_time', '08:00');

    $id = $created->json('data.id');

    expect(AuditLog::query()->where('action', 'shift.created')->count())->toBe(1);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/shifts?search=MOR')
        ->assertOk()
        ->assertJsonPath('data.0.id', $id);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->patchJson("/api/shifts/{$id}", [
            'break_minutes' => 45,
        ])
        ->assertOk()
        ->assertJsonPath('data.break_minutes', 45);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/shifts/{$id}")
        ->assertOk();
});

test('creating a shift with a duplicate code returns SHIFT_CODE_DUPLICATE', function () {
    Company::factory()->create();
    $hr = shiftUser(['can_view_shifts', 'can_manage_shift_definitions']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'DUP',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning 2',
            'code' => 'DUP',
            'start_time' => '09:00',
            'end_time' => '18:00',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'SHIFT_CODE_DUPLICATE');
});

test('invalid time range returns SHIFT_INVALID_TIME_RANGE', function () {
    Company::factory()->create();
    $hr = shiftUser(['can_view_shifts', 'can_manage_shift_definitions']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Bad',
            'code' => 'BAD',
            'start_time' => '17:00',
            'end_time' => '08:00',
            'kind' => 'standard',
            'is_night' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'SHIFT_INVALID_TIME_RANGE');
});

test('night shift may cross midnight', function () {
    Company::factory()->create();
    $hr = shiftUser(['can_view_shifts', 'can_manage_shift_definitions']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Night',
            'code' => 'NIGHT',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'kind' => 'night',
            'is_night' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'NIGHT');
});

test('cannot delete shift with active assignment', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
        'can_assign_shifts',
    ]);

    $shiftId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MOR',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ])
        ->assertCreated();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/shifts/{$shiftId}")
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'SHIFT_IN_USE');
});

test('assignment overlap returns 409', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
        'can_assign_shifts',
    ]);

    $shiftId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MOR2',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ])
        ->assertCreated();

    expect(AuditLog::query()->where('action', 'shift.assignment_created')->count())->toBe(1);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-20',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'SHIFT_ASSIGNMENT_OVERLAP');
});

test('working calendar resolves assigned windows', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $company->id,
        'weekend_days' => [],
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
        'can_assign_shifts',
    ]);

    $shiftId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MOR3',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
        ])
        ->assertCreated();

    $calendar = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$employee->id.'&date_from=2026-08-01&date_to=2026-08-05')
        ->assertOk()
        ->json('data');

    expect($calendar)->toHaveCount(3)
        ->and($calendar[0]['date'])->toBe('2026-08-01')
        ->and($calendar[0]['shift_id'])->toBe($shiftId)
        ->and($calendar[0]['is_holiday'])->toBeFalse()
        ->and($calendar[0]['rest_kind'])->toBe('none')
        ->and($calendar[0]['holiday_name'])->toBeNull()
        ->and($calendar[0]['leave'])->toBeNull();
});

test('working calendar includes weekend and holiday rest days without assignment', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $company->id,
        'weekend_days' => [0, 6],
    ]);
    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2026-08-03',
        'name' => 'Company Day',
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
        'can_assign_shifts',
    ]);

    $shiftId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MOR5',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated()
        ->json('data.id');

    // Mon–Fri assignment only; Sat 2026-08-01 and Sun 2026-08-02 are weekend.
    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-05',
        ])
        ->assertCreated();

    $calendar = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$employee->id.'&date_from=2026-08-01&date_to=2026-08-05')
        ->assertOk()
        ->json('data');

    $byDate = collect($calendar)->keyBy('date');

    expect($byDate['2026-08-01']['rest_kind'])->toBe('weekend')
        ->and($byDate['2026-08-01']['shift_id'])->toBeNull()
        ->and($byDate['2026-08-01']['is_holiday'])->toBeTrue()
        ->and($byDate['2026-08-02']['rest_kind'])->toBe('weekend')
        ->and($byDate['2026-08-03']['rest_kind'])->toBe('holiday')
        ->and($byDate['2026-08-03']['holiday_name'])->toBe('Company Day')
        ->and($byDate['2026-08-03']['shift_id'])->toBe($shiftId)
        ->and($byDate['2026-08-03']['is_holiday'])->toBeTrue()
        ->and($byDate['2026-08-04']['rest_kind'])->toBe('none')
        ->and($byDate['2026-08-04']['shift_id'])->toBe($shiftId);
});

test('working calendar overlays approved leave and keeps shift fields', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $company->id,
        'weekend_days' => [],
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_shift_definitions',
        'can_assign_shifts',
    ]);

    $shiftId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shifts', [
            'name' => 'Morning',
            'code' => 'MOR4',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/shift-assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shiftId,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
        ])
        ->assertCreated();

    $leaveType = LeaveType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Annual Leave',
        'is_paid' => true,
        'requires_balance' => false,
    ]);

    $approved = LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'unit' => 'day',
        'start_date' => '2026-08-02',
        'end_date' => '2026-08-02',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'unit' => 'day',
        'start_date' => '2026-08-03',
        'end_date' => '2026-08-03',
        'quantity' => 1,
        'status' => 'pending',
    ]);

    $calendar = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$employee->id.'&date_from=2026-08-01&date_to=2026-08-03')
        ->assertOk()
        ->json('data');

    $byDate = collect($calendar)->keyBy('date');

    expect($byDate['2026-08-01']['leave'])->toBeNull()
        ->and($byDate['2026-08-02']['shift_id'])->toBe($shiftId)
        ->and($byDate['2026-08-02']['leave']['request_id'])->toBe($approved->id)
        ->and($byDate['2026-08-02']['leave']['leave_type_name'])->toBe('Annual Leave')
        ->and($byDate['2026-08-02']['leave']['coverage'])->toBe('full')
        ->and($byDate['2026-08-02']['leave']['is_paid'])->toBeTrue()
        ->and($byDate['2026-08-03']['leave'])->toBeNull();
});

test('own schedule cannot view other employee calendar', function () {
    $company = Company::factory()->create();
    $self = User::factory()->create();
    $linked = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $self->id,
    ]);
    $other = Employee::factory()->create(['company_id' => $company->id]);

    seedShiftAuth();
    $role = Role::factory()->create(['key' => 'own_sched_'.uniqid(), 'is_system' => false]);
    $ids = Permission::query()->whereIn('key', ['can_view_own_schedule'])->pluck('id');
    $role->permissions()->sync($ids);
    $self->roles()->attach($role);
    $self = $self->fresh('roles.permissions');

    $this->actingAs($self)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$other->id.'&date_from=2026-08-01&date_to=2026-08-02')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');

    $this->actingAs($self)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$linked->id.'&date_from=2026-08-01&date_to=2026-08-02')
        ->assertOk();
});

test('overtime rules replace set works', function () {
    Company::factory()->create();
    $hr = shiftUser([
        'can_view_shifts',
        'can_manage_overtime_rules',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/overtime-rules', [
            'rules' => [
                [
                    'code' => 'STANDARD',
                    'name' => 'Standard OT',
                    'applies_after_minutes' => 0,
                    'allow_before_shift' => false,
                    'night_ot_enabled' => true,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.0.code', 'STANDARD');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/overtime-rules')
        ->assertOk()
        ->assertJsonPath('data.0.code', 'STANDARD');
});

test('WorkingCalendarService smoke resolve without HTTP', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'name' => 'Morning',
        'code' => 'SVC',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-02',
    ]);

    session(['company_id' => $company->id]);

    $days = app(WorkingCalendarService::class)->resolve(
        $employee->id,
        '2026-09-01',
        '2026-09-03',
    );

    expect($days)->toHaveCount(2)
        ->and($days[0]['shift_name'])->toBe('Morning')
        ->and($days[0]['rest_kind'])->toBe('none');
});
