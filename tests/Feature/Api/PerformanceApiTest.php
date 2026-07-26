<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\PerformancePromotionSuggestion;
use App\Models\PerformanceReviewCycle;
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

test('starting a cycle with no participants returns REVIEW_CYCLE_NO_PARTICIPANTS', function () {
    Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Empty cycle',
        ])
        ->assertCreated()
        ->assertJsonPath('data.participants_count', 0)
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'REVIEW_CYCLE_NO_PARTICIPANTS');
});

test('hr can sync participants on a draft cycle', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $first = Employee::factory()->create(['company_id' => $company->id]);
    $second = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$first->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.participants_count', 1)
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.participants_count', 2)
        ->assertJsonCount(2, 'data.participants');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$first->id],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PERFORMANCE_INVALID_TRANSITION');
});

test('goal for non participant is rejected when cycle is set', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_manage_goals']);
    $participant = Employee::factory()->create(['company_id' => $company->id]);
    $outsider = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participant->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $outsider->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Outsider goal',
        ])
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');
});

test('promotion suggestions can be filtered by review_cycle_id', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_evaluate_employee',
        'can_view_promotion_suggestions',
    ]);
    $employeeA = Employee::factory()->create(['company_id' => $company->id]);
    $employeeB = Employee::factory()->create(['company_id' => $company->id]);

    $cycleA = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle A',
            'participant_employee_ids' => [$employeeA->id],
        ])
        ->json('data.id');
    $cycleB = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle B',
            'participant_employee_ids' => [$employeeB->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleA.'/start')->assertOk();
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleB.'/start')->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleA,
            'employee_id' => $employeeA->id,
            'overall_score' => 4.7,
        ])
        ->assertCreated();
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleB,
            'employee_id' => $employeeB->id,
            'overall_score' => 4.8,
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/promotion-suggestions?review_cycle_id='.$cycleA)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.review_cycle_id', $cycleA);
});

test('cycle show includes progress counts', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_view_performance',
        'can_manage_goals',
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
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Active goal',
        ])
        ->assertCreated();

    $completedId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Done goal',
            'progress' => 100,
            'status' => 'completed',
        ])
        ->assertCreated()
        ->json('data.id');

    expect($completedId)->toBeInt();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $employee->id,
            'overall_score' => 4.0,
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertOk()
        ->assertJsonPath('data.participants_count', 1)
        ->assertJsonPath('data.evaluations_count', 1)
        ->assertJsonPath('data.goals_active_count', 1)
        ->assertJsonPath('data.goals_completed_count', 1);
});

test('non participant employee cannot view a review cycle', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $participant = Employee::factory()->create(['company_id' => $company->id]);
    $outsiderEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $outsider = performanceUser(['can_view_performance'], $outsiderEmployee);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participant->id],
        ])
        ->json('data.id');

    $this->actingAs($outsider)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');

    $this->actingAs($outsider)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('participant employee can view their review cycle', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $participantEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $participant = performanceUser(['can_view_performance'], $participantEmployee);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participantEmployee->id],
        ])
        ->json('data.id');

    $this->actingAs($participant)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertOk()
        ->assertJsonPath('data.id', $cycleId);

    $this->actingAs($participant)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('removing a participant deletes their cycle goals', function () {
    $company = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_view_performance',
        'can_manage_goals',
    ]);
    $keep = Employee::factory()->create(['company_id' => $company->id]);
    $remove = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$keep->id, $remove->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $keep->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Keep goal',
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $remove->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Remove goal',
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$keep->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.participants_count', 1)
        ->assertJsonPath('data.goals_active_count', 1);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/goals?review_cycle_id='.$cycleId)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee_id', $keep->id);
});

test('draft review cycle can be deleted', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance', 'can_manage_goals']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Draft cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $employee->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Temp goal',
        ])
        ->assertCreated();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/performance/review-cycles/'.$cycleId)
        ->assertOk();

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertStatus(404);
});

test('syncing participants requires can_manage_review_cycles', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);
    $viewer = performanceUser(['can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($viewer)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$employee->id],
        ])
        ->assertStatus(403);
});

test('syncing participants with an empty array clears the cycle', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->assertJsonPath('data.participants_count', 1)
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.participants_count', 0)
        ->assertJsonCount(0, 'data.participants');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertJsonPath('data.participants_count', 0);
});

test('syncing participants without the key at all is rejected by validation', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$employee->id],
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('syncing participants deduplicates repeated employee ids', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$employee->id, $employee->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.participants_count', 1);
});

test('syncing participants rejects ids outside the current company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_view_performance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $foreignEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
        ])
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->putJson('/api/performance/review-cycles/'.$cycleId.'/participants', [
            'participant_employee_ids' => [$employee->id, $foreignEmployee->id, 999999],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'COMPANY_SCOPE_MISMATCH');

    // Rejected as a whole — no partial roster update.
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/review-cycles/'.$cycleId)
        ->assertJsonPath('data.participants_count', 0);
});

test('creating a review cycle rejects participant ids outside the current company', function () {
    Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles']);
    $foreignEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$foreignEmployee->id],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'COMPANY_SCOPE_MISMATCH');

    expect(PerformanceReviewCycle::query()->count())->toBe(0);
});

test('listing evaluations filtered by a review cycle the actor cannot see is forbidden', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_evaluate_employee']);
    $participant = Employee::factory()->create(['company_id' => $company->id]);
    $outsiderEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $outsider = performanceUser(['can_view_performance'], $outsiderEmployee);

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
            'employee_id' => $participant->id,
            'overall_score' => 4.2,
        ])->assertCreated();

    $this->actingAs($outsider)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/evaluations?review_cycle_id='.$cycleId)
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');
});

test('a participant can list evaluations for their own review cycle', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_evaluate_employee']);
    $participantEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $participant = performanceUser(['can_view_performance'], $participantEmployee);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participantEmployee->id],
        ])
        ->json('data.id');
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles/'.$cycleId.'/start')->assertOk();
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/evaluations', [
            'review_cycle_id' => $cycleId,
            'employee_id' => $participantEmployee->id,
            'overall_score' => 4.2,
        ])->assertCreated();

    $this->actingAs($participant)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/evaluations?review_cycle_id='.$cycleId)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('listing goals filtered by a review cycle the actor cannot see is forbidden', function () {
    $company = Company::factory()->create();
    $hr = performanceUser(['can_manage_review_cycles', 'can_manage_goals']);
    $participant = Employee::factory()->create(['company_id' => $company->id]);
    $outsiderEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $outsider = performanceUser(['can_view_performance'], $outsiderEmployee);

    $cycleId = $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/review-cycles', [
            'name' => 'Cycle',
            'participant_employee_ids' => [$participant->id],
        ])
        ->json('data.id');
    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson('/api/performance/goals', [
            'employee_id' => $participant->id,
            'review_cycle_id' => $cycleId,
            'title' => 'Goal',
        ])->assertCreated();

    $this->actingAs($outsider)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/goals?review_cycle_id='.$cycleId)
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'PERFORMANCE_FORBIDDEN_SCOPE');
});

test('promotion suggestions filtered by a review cycle from another company return no rows', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $hr = performanceUser([
        'can_manage_review_cycles',
        'can_evaluate_employee',
        'can_view_promotion_suggestions',
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
            'overall_score' => 4.7,
        ])->assertCreated();

    $foreignCycle = PerformanceReviewCycle::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->getJson('/api/performance/promotion-suggestions?review_cycle_id='.$foreignCycle->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('active review cycle cannot be deleted', function () {
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
        ->deleteJson('/api/performance/review-cycles/'.$cycleId)
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PERFORMANCE_INVALID_TRANSITION');
});
