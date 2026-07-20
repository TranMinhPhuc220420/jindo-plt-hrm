<?php

namespace App\Events;

use App\Models\OnboardingCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OnboardingCase $onboardingCase) {}
}
