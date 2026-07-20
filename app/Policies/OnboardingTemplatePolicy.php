<?php

namespace App\Policies;

use App\Models\OnboardingTemplate;
use App\Models\User;

class OnboardingTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_onboarding')
            || $user->can('can_manage_onboarding')
            || $user->can('can_manage_onboarding_templates');
    }

    public function view(User $user, OnboardingTemplate $onboardingTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_onboarding_templates');
    }

    public function update(User $user, OnboardingTemplate $onboardingTemplate): bool
    {
        return $user->can('can_manage_onboarding_templates');
    }
}
