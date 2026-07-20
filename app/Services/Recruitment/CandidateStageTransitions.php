<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;

final class CandidateStageTransitions
{
    /**
     * Allowed forward / terminal stage transitions.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED = [
        'applied' => ['screening', 'rejected', 'withdrawn'],
        'screening' => ['interview', 'rejected', 'withdrawn'],
        'interview' => ['offer', 'rejected', 'withdrawn'],
        'offer' => ['hired', 'rejected', 'withdrawn'],
        'hired' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, Candidate::STAGES, true) || ! in_array($to, Candidate::STAGES, true)) {
            return false;
        }

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::ALLOWED[$from] ?? [], true);
    }
}
