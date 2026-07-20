<?php

namespace App\Events;

use App\Models\OnboardingTask;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingTaskCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OnboardingTask $task) {}
}
