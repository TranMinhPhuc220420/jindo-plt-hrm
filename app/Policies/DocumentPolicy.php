<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_company_documents')
            || $user->can('can_view_employee_documents')
            || $user->can('can_upload_own_documents');
    }

    public function view(User $user, Document $document): bool
    {
        return match ($document->owner_type) {
            'company' => $user->can('can_view_company_documents') || $user->can('can_manage_company_documents'),
            'employee' => $user->can('can_view_employee_documents')
                || $user->can('can_manage_employee_documents')
                || ($this->ownsEmployee($user, $document->owner_id) && $user->can('can_upload_own_documents')),
            'candidate' => $user->can('can_view_employee_documents') || $user->can('can_manage_employee_documents'),
            default => false,
        };
    }

    public function create(User $user, string $ownerType, ?int $ownerId = null): bool
    {
        return match ($ownerType) {
            'company' => $user->can('can_manage_company_documents'),
            'employee' => $user->can('can_manage_employee_documents')
                || ($this->ownsEmployee($user, $ownerId) && $user->can('can_upload_own_documents')),
            'candidate' => $user->can('can_manage_employee_documents'),
            default => false,
        };
    }

    public function update(User $user, Document $document): bool
    {
        return $this->manage($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->manage($user, $document);
    }

    private function manage(User $user, Document $document): bool
    {
        return match ($document->owner_type) {
            'company' => $user->can('can_manage_company_documents'),
            'employee' => $user->can('can_manage_employee_documents')
                || ($this->ownsEmployee($user, $document->owner_id) && $user->can('can_upload_own_documents')),
            'candidate' => $user->can('can_manage_employee_documents'),
            default => false,
        };
    }

    private function ownsEmployee(User $user, ?int $ownerId): bool
    {
        if ($ownerId === null) {
            return false;
        }

        return $user->employee !== null && $user->employee->id === $ownerId;
    }
}
