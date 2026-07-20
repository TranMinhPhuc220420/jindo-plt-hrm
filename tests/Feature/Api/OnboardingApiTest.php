<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function onboardingUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'onb');
}

test('cannot start onboarding case without can_manage_onboarding', function () {
    $company = Company::factory()->create();
    $user = onboardingUser(['can_view_onboarding']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'probation']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases', ['employee_id' => $employee->id])
        ->assertForbidden();
});

test('starting a manual case seeds default checklist tasks', function () {
    $company = Company::factory()->create();
    $user = onboardingUser(['can_manage_onboarding']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'probation']);

    $response = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases', [
            'employee_id' => $employee->id,
            'probation_ends_on' => '2026-11-01',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'in_progress');

    $keys = collect($response->json('data.tasks'))->pluck('key');
    expect($keys)->toContain('create_account');
});

test('mandatory pending tasks block case completion', function () {
    $company = Company::factory()->create();
    $user = onboardingUser(['can_manage_onboarding', 'can_complete_onboarding']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'probation']);

    $caseId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases', ['employee_id' => $employee->id])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases/'.$caseId.'/complete')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ONBOARDING_MANDATORY_PENDING');
});

test('completing mandatory tasks then case activates the employee', function () {
    $company = Company::factory()->create();
    $user = onboardingUser([
        'can_manage_onboarding',
        'can_complete_onboarding_task',
        'can_complete_onboarding',
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'probation',
        'email' => 'newhire@example.test',
        'user_id' => null,
    ]);

    $store = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases', ['employee_id' => $employee->id])
        ->assertCreated();

    $caseId = $store->json('data.id');
    $tasks = collect($store->json('data.tasks'));

    $tasks->where('mandatory', true)->each(function (array $task) use ($user) {
        $this->actingAs($user)
            ->withHeaders(spaJsonHeaders())
            ->postJson('/api/onboarding-tasks/'.$task['id'].'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', 'done');
    });

    // create_account task should have provisioned a user account with default password.
    $employee = $employee->fresh();
    expect($employee->user_id)->not->toBeNull();

    $linkedUser = User::query()->findOrFail($employee->user_id);
    expect(Hash::check(
        config('hrm.employee_default_password'),
        $linkedUser->password,
    ))->toBeTrue();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases/'.$caseId.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    expect($employee->fresh()->status)->toBe('active');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-cases/'.$caseId.'/complete')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ONBOARDING_ALREADY_COMPLETED');
});

test('managing templates requires can_manage_onboarding_templates', function () {
    Company::factory()->create();
    $user = onboardingUser(['can_view_onboarding']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-templates', ['name' => 'Standard'])
        ->assertForbidden();
});

test('can create an onboarding template with items', function () {
    Company::factory()->create();
    $user = onboardingUser(['can_manage_onboarding_templates']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/onboarding-templates', [
            'name' => 'Engineering onboarding',
            'items' => [
                ['key' => 'create_account', 'title' => 'Create account', 'mandatory' => true],
                ['key' => 'laptop', 'title' => 'Issue laptop', 'assignee_type' => 'it'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Engineering onboarding')
        ->assertJsonCount(2, 'data.items');
});
