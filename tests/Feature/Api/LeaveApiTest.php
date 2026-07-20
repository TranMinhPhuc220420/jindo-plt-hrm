<?php

use App\Events\LeaveApproved;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveRequested;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WeekendRule;
use Illuminate\Support\Facades\Event;

function seedLeaveAuth(): void
{
    seedAuthCatalog();
}

function leaveUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'leave');
}

function linkLeaveEmployee(User $user, Company $company, array $extra = []): Employee
{
    return Employee::factory()->create(array_merge([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-LV-'.uniqid(),
    ], $extra));
}

test('cannot create leave type without can_manage_leave_types', function () {
    Company::factory()->create();
    $user = leaveUser(['can_view_leave']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-types', [
            'code' => 'ANN',
            'name' => 'Annual',
        ])
        ->assertForbidden();
});

test('request approve updates balance and audit', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    $employee = linkLeaveEmployee($employeeUser, $company);

    $hr = leaveUser([
        'can_view_leave',
        'can_approve_leave',
        'can_manage_leave_types',
        'can_manage_leave_balances',
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'code' => 'ANN',
        'name' => 'Annual',
        'requires_balance' => true,
    ]);

    LeaveBalance::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'period_key' => '2026',
        'entitled' => 12,
        'used' => 0,
        'pending' => 0,
    ]);

    // Wednesday 2026-08-12
    $create = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'unit' => 'day',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'reason' => 'Trip',
        ]);

    $create->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.quantity', 1);

    expect((float) LeaveBalance::query()->first()->pending)->toBe(1.0);

    $id = $create->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/approve", ['note' => 'OK'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $balance = LeaveBalance::query()->first();
    expect((float) $balance->pending)->toBe(0.0);
    expect((float) $balance->used)->toBe(1.0);
    expect(AuditLog::query()->where('action', 'leave.approved')->count())->toBe(1);
});

test('insufficient balance returns LEAVE_BALANCE_INSUFFICIENT', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $user = leaveUser(['can_request_leave', 'can_view_leave']);
    $employee = linkLeaveEmployee($user, $company);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => true,
        'allows_negative' => false,
    ]);

    LeaveBalance::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'period_key' => '2026',
        'entitled' => 0,
        'used' => 0,
        'pending' => 0,
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'LEAVE_BALANCE_INSUFFICIENT');
});

test('holiday only range returns LEAVE_INVALID_DATES', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $user = leaveUser(['can_request_leave', 'can_view_leave']);
    linkLeaveEmployee($user, $company);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2026-08-12',
        'name' => 'Company Day',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'LEAVE_INVALID_DATES');
});

test('overlapping request returns LEAVE_OVERLAPPING_REQUEST', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $user = leaveUser(['can_request_leave', 'can_view_leave']);
    $employee = linkLeaveEmployee($user, $company);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'start_date' => '2026-08-12',
        'end_date' => '2026-08-13',
        'status' => 'pending',
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-13',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'LEAVE_OVERLAPPING_REQUEST');
});

test('illegal transition returns LEAVE_INVALID_TRANSITION', function () {
    $company = Company::factory()->create();
    $hr = leaveUser([
        'can_approve_leave',
        'can_manage_leave_types',
        'can_view_leave',
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $type = LeaveType::factory()->create(['company_id' => $company->id, 'requires_balance' => false]);

    $request = LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => 'approved',
        'start_date' => '2026-08-12',
        'end_date' => '2026-08-12',
        'quantity' => 1,
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$request->id}/approve")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'LEAVE_INVALID_TRANSITION');
});

test('manager cannot approve non-report leave', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    $employee = linkLeaveEmployee($employeeUser, $company);

    $managerUser = leaveUser(['can_view_leave', 'can_approve_leave']);
    linkLeaveEmployee($managerUser, $company);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    $create = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated();

    $id = $create->json('data.id');

    $this->actingAs($managerUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/approve")
        ->assertForbidden();
});

test('manager can approve direct report leave', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $managerUser = leaveUser(['can_view_leave', 'can_approve_leave']);
    $manager = linkLeaveEmployee($managerUser, $company);

    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    linkLeaveEmployee($employeeUser, $company, ['manager_id' => $manager->id]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    $create = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated();

    $id = $create->json('data.id');

    $this->actingAs($managerUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

test('reject releases pending balance', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    $employee = linkLeaveEmployee($employeeUser, $company);

    $hr = leaveUser([
        'can_view_leave',
        'can_approve_leave',
        'can_manage_leave_balances',
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => true,
    ]);

    LeaveBalance::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'period_key' => '2026',
        'entitled' => 5,
        'used' => 0,
        'pending' => 0,
    ]);

    $id = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/reject", ['reason' => 'Coverage'])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $balance = LeaveBalance::query()->first();
    expect((float) $balance->pending)->toBe(0.0);
    expect((float) $balance->used)->toBe(0.0);
    expect(AuditLog::query()->where('action', 'leave.rejected')->count())->toBe(1);
});

test('requesting and approving leave fires LeaveRequested then LeaveApproved', function () {
    Event::fake([LeaveRequested::class, LeaveApproved::class]);

    $company = Company::factory()->create();
    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    linkLeaveEmployee($employeeUser, $company);
    $hr = leaveUser(['can_view_leave', 'can_approve_leave', 'can_manage_leave_balances']);
    $type = LeaveType::factory()->create(['company_id' => $company->id, 'requires_balance' => false]);

    $id = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated()
        ->json('data.id');

    Event::assertDispatched(LeaveRequested::class, fn ($event) => $event->leaveRequest->id === $id);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/approve")
        ->assertOk();

    Event::assertDispatched(LeaveApproved::class, fn ($event) => $event->leaveRequest->id === $id);
});

test('rejecting a leave request fires LeaveRejected', function () {
    Event::fake([LeaveRejected::class]);

    $company = Company::factory()->create();
    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    linkLeaveEmployee($employeeUser, $company);
    $hr = leaveUser(['can_view_leave', 'can_approve_leave', 'can_manage_leave_balances']);
    $type = LeaveType::factory()->create(['company_id' => $company->id, 'requires_balance' => false]);

    $id = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/reject", ['reason' => 'Coverage'])
        ->assertOk();

    Event::assertDispatched(LeaveRejected::class, fn ($event) => $event->leaveRequest->id === $id);
});

test('cancelling a leave request fires LeaveCancelled', function () {
    Event::fake([LeaveCancelled::class]);

    $company = Company::factory()->create();
    $employeeUser = leaveUser(['can_request_leave', 'can_view_leave']);
    linkLeaveEmployee($employeeUser, $company);
    $type = LeaveType::factory()->create(['company_id' => $company->id, 'requires_balance' => false]);

    $id = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $type->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/leave-requests/{$id}/cancel")
        ->assertOk();

    Event::assertDispatched(LeaveCancelled::class, fn ($event) => $event->leaveRequest->id === $id);
});

test('working calendar marks holidays as is_holiday', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create(['company_id' => $company->id, 'weekend_days' => [0, 6]]);

    $user = leaveUser(['can_view_own_schedule', 'can_view_shifts']);
    $employee = linkLeaveEmployee($user, $company);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
    ]);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2026-08-12',
        'name' => 'Company Day',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/working-calendar?employee_id='.$employee->id.'&date_from=2026-08-12&date_to=2026-08-12')
        ->assertOk()
        ->assertJsonPath('data.0.is_holiday', true);
});

test('holidays and weekend rules require can_manage_holidays', function () {
    Company::factory()->create();
    $user = leaveUser(['can_view_leave']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/holidays', [
            'date' => '2026-09-02',
            'name' => 'Independence',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/weekend-rules', [
            'weekend_days' => [0, 6],
        ])
        ->assertForbidden();
});
