<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_candidates')
            || $user->can('can_manage_candidates')
            || $user->can('can_create_offer');
    }

    public function view(User $user, Offer $offer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_create_offer');
    }

    /**
     * Sending an offer requires dual control: create AND approve.
     */
    public function send(User $user, Offer $offer): bool
    {
        return $user->can('can_create_offer') && $user->can('can_approve_offer');
    }

    public function accept(User $user, Offer $offer): bool
    {
        return $user->can('can_hire_candidate');
    }

    public function reject(User $user, Offer $offer): bool
    {
        return $user->can('can_create_offer') || $user->can('can_approve_offer');
    }
}
