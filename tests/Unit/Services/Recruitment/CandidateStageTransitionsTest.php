<?php

use App\Models\Candidate;
use App\Services\Recruitment\CandidateStageTransitions;

test('full transition matrix matches the allowed map', function () {
    foreach (Candidate::STAGES as $from) {
        foreach (Candidate::STAGES as $to) {
            $expected = $from === $to || in_array($to, CandidateStageTransitions::ALLOWED[$from], true);

            expect(CandidateStageTransitions::canTransition($from, $to))
                ->toBe($expected, "transition {$from} -> {$to} did not match the expected allowed map");
        }
    }
});

test('pipeline stages move forward in order', function () {
    expect(CandidateStageTransitions::canTransition('applied', 'screening'))->toBeTrue();
    expect(CandidateStageTransitions::canTransition('screening', 'interview'))->toBeTrue();
    expect(CandidateStageTransitions::canTransition('interview', 'offer'))->toBeTrue();
    expect(CandidateStageTransitions::canTransition('offer', 'hired'))->toBeTrue();
});

test('stages cannot skip ahead in the pipeline', function () {
    expect(CandidateStageTransitions::canTransition('applied', 'interview'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('applied', 'offer'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('applied', 'hired'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('screening', 'hired'))->toBeFalse();
});

test('stages cannot move backwards', function () {
    expect(CandidateStageTransitions::canTransition('offer', 'applied'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('interview', 'screening'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('hired', 'offer'))->toBeFalse();
});

test('rejected, withdrawn, and hired are terminal stages', function () {
    foreach (['hired', 'rejected', 'withdrawn'] as $terminal) {
        foreach (Candidate::STAGES as $to) {
            if ($to === $terminal) {
                continue;
            }
            expect(CandidateStageTransitions::canTransition($terminal, $to))->toBeFalse();
        }
    }
});

test('any active stage can be rejected or withdrawn', function () {
    foreach (['applied', 'screening', 'interview', 'offer'] as $from) {
        expect(CandidateStageTransitions::canTransition($from, 'rejected'))->toBeTrue();
        expect(CandidateStageTransitions::canTransition($from, 'withdrawn'))->toBeTrue();
    }
});

test('unknown stage values are always rejected', function () {
    expect(CandidateStageTransitions::canTransition('bogus', 'applied'))->toBeFalse();
    expect(CandidateStageTransitions::canTransition('applied', 'bogus'))->toBeFalse();
});
