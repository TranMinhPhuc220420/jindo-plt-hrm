<?php

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OnboardingCase;
use App\Models\User;

function hireOpsUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'hire');
}

test('offer acceptance creates onboarding case and probation employee distinct from candidate', function () {
    $company = Company::factory()->create();
    $user = hireOpsUser([
        'can_manage_job_positions',
        'can_manage_candidates',
        'can_create_offer',
        'can_approve_offer',
        'can_hire_candidate',
    ]);

    $openingId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/job-openings', ['title' => 'Data Analyst'])
        ->json('data.id');

    $candidateId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates', [
            'job_opening_id' => $openingId,
            'full_name' => 'Dana Vo',
            'email' => 'dana@example.test',
        ])
        ->json('data.id');

    $offerId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates/'.$candidateId.'/offers', ['probation_ends_on' => '2026-11-01'])
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offerId.'/send')
        ->assertOk();

    $accept = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offerId.'/accept')
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    $caseId = $accept->json('data.onboarding_case_id');
    expect($caseId)->not->toBeNull();

    $candidate = Candidate::query()->find($candidateId);
    expect($candidate->stage)->toBe('hired');

    $employee = Employee::query()->find($candidate->employee_id);
    expect($employee)->not->toBeNull();
    expect($employee->status)->toBe('probation');
    // A candidate row must never be reused as the employee row: it keeps its own
    // record and only references the freshly created employee via employee_id.
    expect(Candidate::query()->whereKey($candidateId)->exists())->toBeTrue();
    expect($candidate->employee_id)->toBe($employee->id);

    $case = OnboardingCase::query()->find($caseId);
    expect($case->employee_id)->toBe($employee->id);
    expect($case->status)->toBe('in_progress');
});

test('mandatory onboarding tasks block completion then employee reaches active', function () {
    $company = Company::factory()->create();
    $user = hireOpsUser([
        'can_manage_onboarding',
        'can_complete_onboarding_task',
        'can_complete_onboarding',
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'probation',
        'email' => 'grace@example.test',
        'user_id' => null,
    ]);

    $store = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases', ['employee_id' => $employee->id])
        ->assertCreated();

    $caseId = $store->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases/'.$caseId.'/complete')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ONBOARDING_MANDATORY_PENDING');

    collect($store->json('data.tasks'))
        ->where('mandatory', true)
        ->each(function (array $task) use ($user) {
            $this->actingAs($user)
                ->withHeaders(spaJsonHeaders())
                ->postJson('/api/onboarding-tasks/'.$task['id'].'/complete')
                ->assertOk();
        });

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases/'.$caseId.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    expect($employee->fresh()->status)->toBe('active');
});

test('asset assignment is audited during operations', function () {
    $company = Company::factory()->create();
    $user = hireOpsUser(['can_assign_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'probation']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', ['employee_id' => $employee->id])
        ->assertCreated();

    expect(AuditLog::query()->where('action', 'asset.assigned')->count())->toBe(1);
});
