<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_assets') || $user->can('can_manage_assets');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_assets');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('can_manage_assets');
    }

    public function retire(User $user, Asset $asset): bool
    {
        return $user->can('can_manage_assets');
    }

    public function replace(User $user, Asset $asset): bool
    {
        return $user->can('can_manage_assets');
    }

    public function assign(User $user, Asset $asset): bool
    {
        return $user->can('can_assign_asset');
    }

    public function returnAsset(User $user, Asset $asset): bool
    {
        return $user->can('can_return_asset');
    }

    public function reportDamage(User $user, Asset $asset): bool
    {
        return $user->can('can_report_asset_damage');
    }

    public function manageMaintenance(User $user, Asset $asset): bool
    {
        return $user->can('can_manage_asset_maintenance');
    }
}
