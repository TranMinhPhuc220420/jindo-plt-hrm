<?php

namespace App\Services\Authorization;

use App\Exceptions\DomainException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function listRoles(int $perPage = 20): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function listPermissions()
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function findRole(int $id): Role
    {
        return Role::query()->with('permissions')->findOrFail($id);
    }

    /**
     * @param  array{key: string, name: string, description?: string|null}  $data
     */
    public function createRole(array $data): Role
    {
        $role = Role::query()->create([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        $this->audit->write(
            action: 'role.created',
            subject: $role,
            payload: ['key' => $role->key, 'name' => $role->name],
        );

        return $role;
    }

    /**
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        $before = $role->only(array_keys($data));
        $role->fill($data);
        $role->save();

        $this->audit->write(
            action: 'role.updated',
            subject: $role,
            payload: [
                'before' => $before,
                'after' => $role->only(array_keys($data)),
            ],
        );

        return $role->fresh('permissions');
    }

    public function deleteRole(Role $role): void
    {
        if ($role->is_system) {
            throw new DomainException(
                message: 'System roles cannot be deleted.',
                errorCode: 'ROLE_SYSTEM_PROTECTED',
                status: 422,
            );
        }

        $payload = ['key' => $role->key, 'name' => $role->name];
        $role->delete();

        $this->audit->write(
            action: 'role.deleted',
            payload: $payload,
        );
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    public function syncPermissions(Role $role, array $permissionKeys): Role
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $permissionKeys)
            ->pluck('id');

        if ($permissionIds->count() !== count(array_unique($permissionKeys))) {
            throw new DomainException(
                message: 'One or more permission keys are invalid.',
                errorCode: 'PERMISSION_KEYS_INVALID',
                status: 422,
            );
        }

        $before = $role->permissions()->pluck('key')->values()->all();
        $role->permissions()->sync($permissionIds);
        $role = $role->fresh('permissions');
        $after = $role->permissions->pluck('key')->values()->all();

        $this->audit->write(
            action: 'role.permissions_synced',
            subject: $role,
            payload: [
                'before' => $before,
                'after' => $after,
            ],
        );

        return $role;
    }

    /**
     * @return Collection<int, Role>
     */
    public function userRoles(User $user)
    {
        return $user->roles()->with('permissions')->orderBy('name')->get();
    }

    /**
     * @param  list<string>  $roleKeys
     */
    public function syncUserRoles(User $user, array $roleKeys): User
    {
        $roleIds = Role::query()
            ->whereIn('key', $roleKeys)
            ->pluck('id');

        if ($roleIds->count() !== count(array_unique($roleKeys))) {
            throw new DomainException(
                message: 'One or more role keys are invalid.',
                errorCode: 'ROLE_KEYS_INVALID',
                status: 422,
            );
        }

        $before = $user->roles()->pluck('key')->values()->all();

        DB::transaction(function () use ($user, $roleIds): void {
            $user->roles()->sync($roleIds);
        });

        $user = $user->fresh('roles.permissions');
        $after = $user->roles->pluck('key')->values()->all();

        $this->audit->write(
            action: 'user.roles_synced',
            subject: $user,
            payload: [
                'before' => $before,
                'after' => $after,
            ],
        );

        return $user;
    }
}
