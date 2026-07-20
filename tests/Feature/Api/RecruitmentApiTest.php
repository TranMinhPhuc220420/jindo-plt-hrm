<?php

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\JobOpening;
use App\Models\Offer;
use App\Models\User;

function recruitmentUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'rec');
}

test('cannot create job opening without can_manage_job_positions', function () {
    Company::factory()->create();
    $user = recruitmentUser(['can_view_candidates']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/job-openings', ['title' => 'Backend Engineer'])
        ->assertForbidden();
});

test('creating a job opening with a duplicate code returns JOB_OPENING_CODE_DUPLICATE', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_job_positions']);
    JobOpening::factory()->create(['company_id' => $company->id, 'code' => 'ENG-001']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/job-openings', ['title' => 'Backend Engineer', 'code' => 'ENG-001'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'JOB_OPENING_CODE_DUPLICATE');
});

test('hr can update and close a job opening', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_job_positions']);
    $opening = JobOpening::factory()->create(['company_id' => $company->id, 'status' => 'open']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->patchJson('/api/job-openings/'.$opening->id, ['title' => 'Senior Backend Engineer'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Senior Backend Engineer');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/job-openings/'.$opening->id.'/close')
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    expect(AuditLog::query()->where('action', 'job_opening.closed')->count())->toBe(1);
});

test('candidate list filters by job_opening_id and stage', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_view_candidates']);
    $openingA = JobOpening::factory()->create(['company_id' => $company->id]);
    $openingB = JobOpening::factory()->create(['company_id' => $company->id]);
    $match = Candidate::factory()->create([
        'company_id' => $company->id,
        'job_opening_id' => $openingA->id,
        'stage' => 'screening',
    ]);
    Candidate::factory()->create([
        'company_id' => $company->id,
        'job_opening_id' => $openingB->id,
        'stage' => 'screening',
    ]);
    Candidate::factory()->create([
        'company_id' => $company->id,
        'job_opening_id' => $openingA->id,
        'stage' => 'applied',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/candidates?job_opening_id='.$openingA->id.'&stage=screening')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

test('hr can update non-pipeline candidate fields', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_candidates']);
    $candidate = Candidate::factory()->create([
        'company_id' => $company->id,
        'full_name' => 'Original Name',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->patchJson('/api/candidates/'.$candidate->id, [
            'full_name' => 'Updated Name',
            'phone' => '0900000000',
        ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Updated Name')
        ->assertJsonPath('data.phone', '0900000000');
});

test('cannot add candidate to a closed job opening', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_candidates']);
    $opening = JobOpening::factory()->create(['company_id' => $company->id, 'status' => 'closed']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates', [
            'job_opening_id' => $opening->id,
            'full_name' => 'Chris Lee',
            'email' => 'chris@example.test',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'JOB_OPENING_CLOSED');
});

test('illegal candidate stage move returns CANDIDATE_INVALID_STAGE', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_candidates']);
    $opening = JobOpening::factory()->create(['company_id' => $company->id, 'status' => 'open']);
    $candidate = Candidate::factory()->create([
        'company_id' => $company->id,
        'job_opening_id' => $opening->id,
        'stage' => 'applied',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates/'.$candidate->id.'/stage', ['stage' => 'offer'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'CANDIDATE_INVALID_STAGE');
});

test('sending an offer requires create and approve permissions', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_create_offer']);
    $opening = JobOpening::factory()->create(['company_id' => $company->id]);
    $candidate = Candidate::factory()->create(['company_id' => $company->id, 'job_opening_id' => $opening->id]);
    $offer = Offer::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offer->id.'/send')
        ->assertForbidden();
});

test('accepting a draft offer returns OFFER_NOT_PENDING', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_hire_candidate']);
    $opening = JobOpening::factory()->create(['company_id' => $company->id]);
    $candidate = Candidate::factory()->create(['company_id' => $company->id, 'job_opening_id' => $opening->id]);
    $offer = Offer::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offer->id.'/accept')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'OFFER_NOT_PENDING');
});

test('full recruitment flow from opening to accepted offer', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser([
        'can_manage_job_positions',
        'can_view_candidates',
        'can_manage_candidates',
        'can_create_offer',
        'can_approve_offer',
        'can_hire_candidate',
    ]);

    $openingId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/job-openings', ['title' => 'Backend Engineer'])
        ->assertCreated()
        ->json('data.id');

    $candidateId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates', [
            'job_opening_id' => $openingId,
            'full_name' => 'Chris Lee',
            'email' => 'chris@example.test',
        ])
        ->assertCreated()
        ->json('data.id');

    $offerId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates/'.$candidateId.'/offers', [
            'title' => 'Backend Engineer',
            'salary_amount' => 20000000,
            'probation_ends_on' => '2026-11-01',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offerId.'/send')
        ->assertOk()
        ->assertJsonPath('data.status', 'sent');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/offers/'.$offerId.'/accept', ['accepted_at' => '2026-07-20T10:00:00Z'])
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    $candidate = Candidate::query()->find($candidateId);
    expect($candidate->stage)->toBe('hired');
    expect($candidate->employee_id)->not->toBeNull();

    $employee = Employee::query()->find($candidate->employee_id);
    expect($employee)->not->toBeNull();
    expect($employee->status)->toBe('probation');
    // Candidate row still exists independently of the new employee row.
    expect(Candidate::query()->whereKey($candidateId)->exists())->toBeTrue();
});

test('scheduling an interview requires can_manage_interviews', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_view_candidates', 'can_manage_candidates']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates/'.$candidate->id.'/interviews', [
            'scheduled_at' => '2026-08-01 10:00:00',
        ])
        ->assertForbidden();
});

test('hr can schedule and list interviews for a candidate', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_view_candidates', 'can_manage_interviews']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);

    $interviewId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/candidates/'.$candidate->id.'/interviews', [
            'scheduled_at' => '2026-08-01 10:00:00',
            'mode' => 'onsite',
            'location' => 'HQ',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'scheduled')
        ->json('data.id');

    expect(AuditLog::query()->where('action', 'interview.scheduled')->count())->toBe(1);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/candidates/'.$candidate->id.'/interviews')
        ->assertOk()
        ->assertJsonPath('data.0.id', $interviewId);
});

test('evaluating an interview requires can_manage_interviews', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_view_candidates', 'can_manage_candidates']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);
    $interview = Interview::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/interviews/'.$interview->id.'/evaluation', [
            'rating' => 4,
            'recommendation' => 'hire',
        ])
        ->assertForbidden();
});

test('submitting an evaluation marks the interview completed', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_interviews']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);
    $interview = Interview::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/interviews/'.$interview->id.'/evaluation', [
            'rating' => 5,
            'recommendation' => 'hire',
            'comments' => 'Strong candidate.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.recommendation', 'hire');

    expect($interview->fresh()->status)->toBe('completed');
    expect(AuditLog::query()->where('action', 'interview.evaluated')->count())->toBe(1);
});

test('evaluation rejects a rating outside 1-5', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_interviews']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);
    $interview = Interview::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/interviews/'.$interview->id.'/evaluation', ['rating' => 6])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('evaluation rejects an unknown recommendation value', function () {
    $company = Company::factory()->create();
    $user = recruitmentUser(['can_manage_interviews']);
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);
    $interview = Interview::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/interviews/'.$interview->id.'/evaluation', ['recommendation' => 'maybe'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});
