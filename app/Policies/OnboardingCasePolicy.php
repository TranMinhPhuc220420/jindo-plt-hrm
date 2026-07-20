<?php

namespace App\Policies;

use App\Models\OnboardingCase;
use App\Models\User;

class OnboardingCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_onboarding') || $user->can('can_manage_onboarding');
    }

    public function view(User $user, OnboardingCase $onboardingCase): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_onboarding');
    }

    public function complete(User $user, OnboardingCase $onboardingCase): bool
    {
        return $user->can('can_complete_onboarding');
    }

    public function completeTask(User $user, OnboardingCase $onboardingCase): bool
    {
        return $user->can('can_complete_onboarding_task') || $user->can('can_manage_onboarding');
    }

    public function reopenTask(User $user, OnboardingCase $onboardingCase): bool
    {
        return $user->can('can_manage_onboarding');
    }
}
