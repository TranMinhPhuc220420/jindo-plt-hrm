<?php

namespace App\Events;

use App\Models\PerformanceReviewCycle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewCycleStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public PerformanceReviewCycle $cycle) {}
}
