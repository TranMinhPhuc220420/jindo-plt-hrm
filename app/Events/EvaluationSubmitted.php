<?php

namespace App\Events;

use App\Models\PerformanceEvaluation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public PerformanceEvaluation $evaluation) {}
}
