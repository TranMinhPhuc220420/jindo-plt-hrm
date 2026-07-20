<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformancePromotionSuggestion;
use App\Models\User;

function performanceUser(array $permissionKeys, ?Employee $employee = null): User
{
    return actingUser($permissionKeys, $employee, 'perf');
}

test('review cycle runs end to end with promotion suggestion', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_view_performance',
        'can_evaluate_employee',
        'can_manage_goals',
        'can_view_promotion_suggestions',
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'H2 2026 Review',
            'framework' => 'okr',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
            'participant_employee_ids' => [$employee->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->json('data.id');

    // Cannot evaluate before the cycle opens.
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.6,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'REVIEW_CYCLE_NOT_OPEN');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Ship roadmap',
            'type' => 'okr',
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.6,
            'summary' => 'Outstanding',
            'ratings' => [['criterion' => 'delivery', 'score' => 4.8]],
        ])
        ->assertCreated();

    // High score creates a promotion suggestion.
    $suggestions = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/promotion-suggestions')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $suggestionId = $suggestions->json('data.0.id');
    $originalPosition = $employee->position_id;

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/promotion-suggestions/'.$suggestionId.'/acknowledge')
        ->assertOk()
        ->assertJsonPath('data.status', 'acknowledged');

    // Acknowledging is advisory only — the employee is not modified.
    expect($employee->fresh()->position_id)->toBe($originalPosition);

    // Duplicate evaluation is rejected.
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.0,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'EVALUATION_DUPLICATE');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/finalize')
        ->assertOk()
        ->assertJsonPath('data.status', 'finalized');
});

test('evaluation below threshold does not create promotion suggestion', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_view_performance',
        'can_evaluate_employee',
    ]);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 3.9,
        ])
        ->assertCreated();

    expect(PerformancePromotionSuggestion::query()->count())->toBe(0);
});

test('non participant cannot be evaluated', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_evaluate_employee',
    ]);
    $participant = Employee::factory()->create(['company_id' => $company->id]);
    $outsider = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participant->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $outsider->id,
            'overall_score' => 4.0,
        ])
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');
});

test('manager can only evaluate direct reports', function () {
    $company = Company::factory()->create();
    $managerEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $manager = performanceUser(['can_evaluate_employee', 'can_view_performance'], $managerEmployee);

    // A separate HR-capable actor to create/start the cycle.
    $hr = performanceUser(['can_manage_review_cycles']);

    $report = Employee::factory()->create([
        'company_id' => $company->id,
        'manager_id' => $managerEmployee->id,
    ]);
    $other = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$report->id, $other->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    // Direct report: allowed.
    $this->actingAs($manager)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $report->id,
            'overall_score' => 4.1,
        ])
        ->assertCreated();

    // Not a direct report: forbidden scope.
    $this->actingAs($manager)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $other->id,
            'overall_score' => 4.1,
        ])
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');
});

test('creating a review cycle requires manage permission', function () {
    Company::factory()->create();
    $user = performanceUser(['can_view_performance']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', ['name' => 'X'])
        ->assertStatus(403);
});

test('referencing an employee from another company returns COMPANY_SCOPE_MISMATCH', function () {
    // The first company created becomes the active one resolved by CompanyContext.
    Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $hr = performanceUser(['can_manage_goals']);
    $foreignEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $foreignEmployee->id,
            'title' => 'Ship roadmap',
        ])
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'COMPANY_SCOPE_MISMATCH');
});

test('creating a goal requires can_manage_goals', function () {
    $company = Company::factory()->create();
    $user = performanceUser(['can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'title' => 'Ship roadmap',
        ])
        ->assertStatus(403);
});

test('hr can update a goal', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_goals']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $goalId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'title' => 'Ship roadmap',
            'progress' => 10,
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->patchJson('/api/performance/goals/'.$goalId, [
            'progress' => 75,
            'status' => 'completed',
        ])
        ->assertOk()
        ->assertJsonPath('data.progress', 75)
        ->assertJsonPath('data.status', 'completed');
});

test('starting a non-draft review cycle returns PERFORMANCE_INVALID_TRANSITION', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PERFORMANCE_INVALID_TRANSITION');
});

test('finalizing a draft review cycle returns PERFORMANCE_INVALID_TRANSITION', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/finalize')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PERFORMANCE_INVALID_TRANSITION');
});

test('submitting an evaluation requires can_evaluate_employee', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);
    $unauthorized = performanceUser(['can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($unauthorized)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.0,
        ])
        ->assertStatus(403);
});

test('evaluation rejects an overall_score outside 0-5', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_evaluate_employee']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 5.5,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('evaluation rejects a missing required review_cycle_id', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_evaluate_employee']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'employee_id' => $employee->id,
            'overall_score' => 4.0,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});
