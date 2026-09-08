<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOpening;
use App\Policies\CandidatePolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('viewAny allows view or manage candidates permission', function () {
    expect((new CandidatePolicy)->viewAny(actingUser(['can_view_candidates'], prefix: 'cv')))->toBeTrue()
        ->and((new CandidatePolicy)->viewAny(actingUser(['can_manage_candidates'], prefix: 'cm')))->toBeTrue()
        ->and((new CandidatePolicy)->viewAny(actingUser([], prefix: 'cn')))->toBeFalse();
});

test('create and update require can_manage_candidates', function () {
    $company = Company::factory()->create();
    $opening = JobOpening::factory()->create(['company_id' => $company->id]);
    $candidate = Candidate::factory()->create([
        'company_id' => $company->id,
        'job_opening_id' => $opening->id,
    ]);

    $viewer = actingUser(['can_view_candidates'], prefix: 'cv2');
    $manager = actingUser(['can_manage_candidates'], prefix: 'cm2');
    $policy = new CandidatePolicy;

    expect($policy->create($viewer))->toBeFalse()
        ->and($policy->update($viewer, $candidate))->toBeFalse()
        ->and($policy->create($manager))->toBeTrue()
        ->and($policy->update($manager, $candidate))->toBeTrue();
});

test('hire requires can_hire_candidate', function () {
    $company = Company::factory()->create();
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);

    expect((new CandidatePolicy)->hire(actingUser(['can_manage_candidates'], prefix: 'ch'), $candidate))->toBeFalse()
        ->and((new CandidatePolicy)->hire(actingUser(['can_hire_candidate'], prefix: 'ch2'), $candidate))->toBeTrue();
});

test('manageInterviews requires can_manage_interviews', function () {
    $company = Company::factory()->create();
    $candidate = Candidate::factory()->create(['company_id' => $company->id]);

    expect((new CandidatePolicy)->manageInterviews(actingUser(['can_view_candidates'], prefix: 'ci'), $candidate))->toBeFalse()
        ->and((new CandidatePolicy)->manageInterviews(actingUser(['can_manage_interviews'], prefix: 'ci2'), $candidate))->toBeTrue();
});
