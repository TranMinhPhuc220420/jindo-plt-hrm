<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\PerformanceReviewCycle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function insightAdmin(): User
{
    seedAuthCatalog();

    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('key', 'admin')->firstOrFail());

    return $user->fresh('roles.permissions');
}

function insightUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'insight');
}

test('insight exit criteria: cycle, inbox, export, dashboard, audit', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $admin = insightAdmin();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'status' => 'active',
    ]);
    $leaveType = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);

    // --- Inbox after a leave event ---
    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/leave-requests', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
        ])
        ->assertCreated();

    // The admin is both the requester and (via the admin role) an eligible
    // approver, so the inbox also gets a leave.pending_approval entry —
    // filter by type instead of relying on inbox ordering between the two.
    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/notifications?unread_only=1&type=leave.requested')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'leave.requested');

    // --- Review cycle end to end ---
    $cycleId = $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Annual',
            'framework' => 'mixed',
            'participant_employee_ids' => [$employee->id],
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.7,
        ])
        ->assertCreated();

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/finalize')
        ->assertOk()
        ->assertJsonPath('data.status', 'finalized');

    // --- Export flow (202 -> ready) ---
    $exportId = $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/reports/exports', ['report' => 'employees', 'format' => 'csv'])
        ->assertStatus(202)
        ->json('data.id');

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/exports/'.$exportId)
        ->assertOk()
        ->assertJsonPath('data.status', 'ready');

    // --- Dashboard KPIs ---
    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'scope',
            'active_employees',
            'attendance_today_rate',
            'pending_leave_requests',
            'new_hires_month',
            'open_payroll_runs',
            'unread_notifications',
            'attendance_last_7_days',
            'employees_by_status',
            'recent_hires',
            'pending_actions',
            'upcoming',
            'recent_activity',
        ]])
        ->assertJsonPath('data.scope', 'company');

    // --- Audit visibility for critical actions ---
    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?action=performance.evaluation_submitted')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'performance.evaluation_submitted');

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?action=leave.requested')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'leave.requested');

    expect(AuditLog::query()->where('action', 'performance.promotion_suggested')->count())->toBe(1);
});

test('payroll report gate blocks users without payroll report permission', function () {
    Company::factory()->create();
    $user = insightUser(['can_view_employee_reports']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/payroll')
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'REPORT_FORBIDDEN');
});

test('audit log filters support subject_type and date_from', function () {
    $company = Company::factory()->create();
    $admin = insightAdmin();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'C',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?subject_type='.urlencode(PerformanceReviewCycle::class))
        ->assertOk()
        ->assertJsonPath('data.0.action', 'performance.cycle_created');

    $this->actingAs($admin)->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?date_from='.now()->toDateString())
        ->assertOk()
        ->assertJsonPath('data.0.subject_id', $cycleId);
});
