<?php

namespace App\Events;

use App\Models\Candidate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public string $from,
        public string $to,
    ) {}
}
