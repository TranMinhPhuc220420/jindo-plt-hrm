<?php

namespace App\Events;

use App\Models\OnboardingCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OnboardingCase $onboardingCase) {}
}
