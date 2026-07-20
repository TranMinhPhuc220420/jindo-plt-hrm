import { useCallback, useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import type { Employee } from '@/lib/api/modules/employees';
import * as rolesApi from '@/lib/api/modules/roles';
import type { Permission, Role } from '@/lib/api/modules/roles';
import { useAuth } from '@/lib/auth/auth-context';
import { cn } from '@/lib/utils';

const PERMISSION_GROUP_ORDER = [
    'organization',
    'authorization',
    'settings',
    'audit',
    'employee',
    'shift',
    'attendance',
    'leave',
    'payroll',
    'documents',
    'assets',
    'recruitment',
    'onboarding',
    'performance',
    'reports',
    'notifications',
] as const;

const OTHER_GROUP = '__other__';

function roleDisplayName(
    role: Role,
    t: (key: string, options?: Record<string, unknown>) => string,
): string {
    if (role.is_system) {
        return t(`system_roles.${role.key}`, { defaultValue: role.name });
    }

    return role.name;
}

function permissionGroupKey(permission: Permission): string {
    return permission.group?.trim() || OTHER_GROUP;
}

function groupPermissions(permissions: Permission[]): {
    groups: string[];
    byGroup: Record<string, Permission[]>;
} {
    const byGroup: Record<string, Permission[]> = {};

    for (const permission of permissions) {
        const key = permissionGroupKey(permission);
        (byGroup[key] ??= []).push(permission);
    }

    const known = PERMISSION_GROUP_ORDER.filter((group) => group in byGroup);
    const extras = Object.keys(byGroup)
        .filter(
            (group) =>
                group !== OTHER_GROUP &&
                !PERMISSION_GROUP_ORDER.includes(
                    group as (typeof PERMISSION_GROUP_ORDER)[number],
                ),
        )
        .sort();

    const groups = [
        ...known,
        ...extras,
        ...(OTHER_GROUP in byGroup ? [OTHER_GROUP] : []),
    ];

    return { groups, byGroup };
}

export default function RolesPage() {
    const { t } = useTranslation(['roles', 'common', 'permissions']);
    const { can } = useAuth();
    const canManage = can('can_manage_roles');
    const canAssign = can('can_assign_roles');

    const [roles, setRoles] = useState<Role[]>([]);
    const [permissions, setPermissions] = useState<Permission[]>([]);
    const [selectedRoleId, setSelectedRoleId] = useState<number | null>(null);
    const [selectedKeys, setSelectedKeys] = useState<string[]>([]);
    const [activeGroup, setActiveGroup] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const { groups, byGroup } = useMemo(
        () => groupPermissions(permissions),
        [permissions],
    );

    const activePermissions = useMemo(() => {
        if (activeGroup === null) {
            return [];
        }

        return byGroup[activeGroup] ?? [];
    }, [activeGroup, byGroup]);

    const [newKey, setNewKey] = useState('');
    const [newName, setNewName] = useState('');

    const [assignEmployeeId, setAssignEmployeeId] = useState<number | null>(
        null,
    );
    const [assignUserId, setAssignUserId] = useState<number | null>(null);
    const [assignRoleKeys, setAssignRoleKeys] = useState<string[]>([]);
    const [assignLoading, setAssignLoading] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [rolesResult, permissionList] = await Promise.all([
                rolesApi.listRoles(),
                rolesApi.listPermissions(),
            ]);

            setRoles(rolesResult.data);
            setPermissions(permissionList);

            const { groups: nextGroups } = groupPermissions(permissionList);

            setActiveGroup((current) => {
                if (current !== null && nextGroups.includes(current)) {
                    return current;
                }

                return nextGroups[0] ?? null;
            });

            const first = rolesResult.data[0];

            if (first && selectedRoleId === null) {
                setSelectedRoleId(first.id);
                setSelectedKeys(
                    (first.permissions ?? []).map(
                        (permission) => permission.key,
                    ),
                );
            }
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('error_load'));
        } finally {
            setLoading(false);
        }
    }, [selectedRoleId, t]);

    useEffect(() => {
        void load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function selectRole(role: Role) {
        setSelectedRoleId(role.id);
        setSelectedKeys((role.permissions ?? []).map((p) => p.key));
        setActiveGroup((current) => {
            if (current !== null && groups.includes(current)) {
                return current;
            }

            return groups[0] ?? null;
        });
    }

    function togglePermission(key: string) {
        setSelectedKeys((current) =>
            current.includes(key)
                ? current.filter((item) => item !== key)
                : [...current, key],
        );
    }

    function selectAllInActiveGroup() {
        const keys = activePermissions.map((permission) => permission.key);

        setSelectedKeys((current) => [
            ...current,
            ...keys.filter((key) => !current.includes(key)),
        ]);
    }

    function clearActiveGroup() {
        const keys = new Set(
            activePermissions.map((permission) => permission.key),
        );

        setSelectedKeys((current) => current.filter((key) => !keys.has(key)));
    }

    function groupLabel(group: string): string {
        if (group === OTHER_GROUP) {
            return t('group_other');
        }

        return t(`permission_groups.${group}`, { defaultValue: group });
    }

    function groupSelectedCount(group: string): number {
        const keys = byGroup[group] ?? [];

        return keys.filter((permission) =>
            selectedKeys.includes(permission.key),
        ).length;
    }

    async function handleSavePermissions() {
        if (!selectedRoleId) {
            return;
        }

        setSaving(true);

        try {
            const updated = await rolesApi.syncRolePermissions(
                selectedRoleId,
                selectedKeys,
            );
            toast.success(t('toast_permissions_updated'));
            setRoles((current) =>
                current.map((role) =>
                    role.id === updated.id ? updated : role,
                ),
            );
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('toast_permissions_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleCreateRole(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const role = await rolesApi.createRole({
                key: newKey,
                name: newName,
            });
            toast.success(t('toast_role_created'));
            setNewKey('');
            setNewName('');
            setRoles((current) => [...current, role]);
            selectRole(role);
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('toast_role_create_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleDeleteRole(role: Role) {
        if (role.is_system) {
            toast.error(t('toast_system_role_protected'));

            return;
        }

        if (
            !confirm(
                t('confirm_delete_role', {
                    name: roleDisplayName(role, t),
                }),
            )
        ) {
            return;
        }

        try {
            await rolesApi.deleteRole(role.id);
            toast.success(t('toast_role_deleted'));
            setRoles((current) =>
                current.filter((item) => item.id !== role.id),
            );

            if (selectedRoleId === role.id) {
                setSelectedRoleId(null);
                setSelectedKeys([]);
            }
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('toast_role_delete_failed'),
            );
        }
    }

    function handleAssignEmployeeChange(
        id: number | null,
        employee: Employee | null,
    ) {
        setAssignEmployeeId(id);
        setAssignRoleKeys([]);

        const userId = employee?.user_id ?? null;
        setAssignUserId(userId);

        if (employee !== null && userId === null) {
            toast.error(t('toast_employee_no_user'));
        }
    }

    async function handleLoadUserRoles() {
        if (assignUserId === null) {
            toast.error(
                assignEmployeeId === null
                    ? t('toast_select_employee')
                    : t('toast_employee_no_user'),
            );

            return;
        }

        setAssignLoading(true);

        try {
            const userRoles = await rolesApi.getUserRoles(assignUserId);
            setAssignRoleKeys(userRoles.map((role) => role.key));
            toast.success(t('toast_user_roles_loaded'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('toast_user_roles_load_failed'),
            );
        } finally {
            setAssignLoading(false);
        }
    }

    async function handleSaveUserRoles() {
        if (assignUserId === null) {
            toast.error(
                assignEmployeeId === null
                    ? t('toast_select_employee')
                    : t('toast_employee_no_user'),
            );

            return;
        }

        setAssignLoading(true);

        try {
            await rolesApi.syncUserRoles(assignUserId, assignRoleKeys);
            toast.success(t('toast_user_roles_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('toast_user_roles_update_failed'),
            );
        } finally {
            setAssignLoading(false);
        }
    }

    function toggleAssignRole(key: string) {
        setAssignRoleKeys((current) =>
            current.includes(key)
                ? current.filter((item) => item !== key)
                : [...current, key],
        );
    }

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            permission="can_view_roles"
        >
            {loading && <LoadingState label={t('loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && (
                <div className="min-w-0 space-y-8">
                    <div className="grid min-w-0 gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                        <div className="min-w-0 space-y-3">
                            <h3 className="text-sm font-semibold">
                                {t('section_roles')}
                            </h3>
                            {roles.length === 0 ? (
                                <EmptyState message={t('empty_roles')} />
                            ) : (
                                <ul className="space-y-1">
                                    {roles.map((role) => (
                                        <li key={role.id}>
                                            <button
                                                type="button"
                                                onClick={() => selectRole(role)}
                                                className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm ${
                                                    selectedRoleId === role.id
                                                        ? 'bg-primary-deep text-white'
                                                        : 'hover:bg-muted'
                                                }`}
                                            >
                                                <span>
                                                    {roleDisplayName(role, t)}
                                                </span>
                                                {role.is_system && (
                                                    <span className="text-[10px] opacity-80">
                                                        {t('system_badge')}
                                                    </span>
                                                )}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}

                            <PermissionGate permission="can_manage_roles">
                                <form
                                    onSubmit={handleCreateRole}
                                    className="space-y-2 border-t border-border pt-3"
                                >
                                    <Label htmlFor="role-key">
                                        {t('role_key')}
                                    </Label>
                                    <Input
                                        id="role-key"
                                        value={newKey}
                                        onChange={(e) =>
                                            setNewKey(e.target.value)
                                        }
                                        placeholder={t('role_key_placeholder')}
                                        required
                                    />
                                    <Label htmlFor="role-name">
                                        {t('role_name')}
                                    </Label>
                                    <Input
                                        id="role-name"
                                        value={newName}
                                        onChange={(e) =>
                                            setNewName(e.target.value)
                                        }
                                        placeholder={t('role_name_placeholder')}
                                        required
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={saving}
                                    >
                                        {t('create_role')}
                                    </Button>
                                </form>
                            </PermissionGate>
                        </div>

                        <div className="min-w-0 space-y-4">
                            {selectedRoleId === null ? (
                                <EmptyState message={t('empty_select_role')} />
                            ) : (
                                <>
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <h3 className="text-sm font-semibold">
                                            {t('section_permissions')}
                                        </h3>
                                        <PermissionGate permission="can_manage_roles">
                                            <div className="flex flex-wrap gap-2">
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        void handleSavePermissions()
                                                    }
                                                    disabled={
                                                        saving || !canManage
                                                    }
                                                >
                                                    {t('save_permissions')}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const role = roles.find(
                                                            (item) =>
                                                                item.id ===
                                                                selectedRoleId,
                                                        );

                                                        if (role) {
                                                            void handleDeleteRole(
                                                                role,
                                                            );
                                                        }
                                                    }}
                                                >
                                                    {t('delete_role')}
                                                </Button>
                                            </div>
                                        </PermissionGate>
                                    </div>

                                    {groups.length === 0 ? (
                                        <EmptyState
                                            message={t('empty_select_role')}
                                        />
                                    ) : (
                                        <>
                                            <div className="-mx-1 w-full overflow-x-auto px-1 pb-1">
                                                <div className="flex w-max max-w-none gap-1 rounded-lg bg-muted p-1">
                                                    {groups.map((group) => {
                                                        const total =
                                                            byGroup[group]
                                                                ?.length ?? 0;
                                                        const selected =
                                                            groupSelectedCount(
                                                                group,
                                                            );

                                                        return (
                                                            <button
                                                                key={group}
                                                                type="button"
                                                                onClick={() =>
                                                                    setActiveGroup(
                                                                        group,
                                                                    )
                                                                }
                                                                className={cn(
                                                                    'inline-flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-sm whitespace-nowrap transition-colors',
                                                                    activeGroup ===
                                                                        group
                                                                        ? 'bg-background text-foreground shadow-xs'
                                                                        : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
                                                                )}
                                                            >
                                                                <span>
                                                                    {groupLabel(
                                                                        group,
                                                                    )}
                                                                </span>
                                                                <span
                                                                    className={cn(
                                                                        'rounded-full px-1.5 py-0.5 text-[10px] font-medium',
                                                                        activeGroup ===
                                                                            group
                                                                            ? 'bg-primary-deep/10 text-primary-deep'
                                                                            : 'bg-background/80 text-muted-foreground',
                                                                    )}
                                                                >
                                                                    {selected}/
                                                                    {total}
                                                                </span>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>

                                            {canManage && (
                                                <div className="flex flex-wrap gap-2">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={
                                                            selectAllInActiveGroup
                                                        }
                                                        disabled={
                                                            activePermissions.length ===
                                                            0
                                                        }
                                                    >
                                                        {t('select_all_group')}
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={
                                                            clearActiveGroup
                                                        }
                                                        disabled={
                                                            activePermissions.length ===
                                                            0
                                                        }
                                                    >
                                                        {t('clear_group')}
                                                    </Button>
                                                </div>
                                            )}

                                            <div className="grid min-w-0 gap-2 sm:grid-cols-2">
                                                {activePermissions.map(
                                                    (permission) => (
                                                        <label
                                                            key={permission.key}
                                                            className="flex min-w-0 items-start gap-2 rounded-md border border-border px-3 py-2 text-sm"
                                                        >
                                                            <Checkbox
                                                                className="mt-0.5 shrink-0"
                                                                checked={selectedKeys.includes(
                                                                    permission.key,
                                                                )}
                                                                disabled={
                                                                    !canManage
                                                                }
                                                                onCheckedChange={() =>
                                                                    togglePermission(
                                                                        permission.key,
                                                                    )
                                                                }
                                                            />
                                                            <span className="min-w-0 flex-1">
                                                                <span className="block font-medium break-words">
                                                                    {t(
                                                                        `${permission.key}.name`,
                                                                        {
                                                                            ns: 'permissions',
                                                                            defaultValue:
                                                                                permission.name,
                                                                        },
                                                                    )}
                                                                </span>
                                                                <span className="mt-0.5 block text-xs break-words text-muted-foreground">
                                                                    {t(
                                                                        `${permission.key}.description`,
                                                                        {
                                                                            ns: 'permissions',
                                                                            defaultValue:
                                                                                permission.description ??
                                                                                '',
                                                                        },
                                                                    )}
                                                                </span>
                                                            </span>
                                                        </label>
                                                    ),
                                                )}
                                            </div>
                                        </>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                    <PermissionGate permission="can_assign_roles">
                        <div className="space-y-4 border-t border-border pt-6">
                            <div>
                                <h3 className="text-sm font-semibold">
                                    {t('assign_title')}
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    {t('assign_help')}
                                </p>
                            </div>
                            <div className="flex flex-wrap items-end gap-3">
                                <EmployeePickerField
                                    id="assign-employee"
                                    label={t('assign_employee')}
                                    value={assignEmployeeId}
                                    onChange={handleAssignEmployeeChange}
                                    disabled={!canAssign}
                                    className="max-w-sm min-w-[16rem] flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={
                                        assignLoading ||
                                        !canAssign ||
                                        assignUserId === null
                                    }
                                    onClick={() => void handleLoadUserRoles()}
                                >
                                    {t('load_roles')}
                                </Button>
                                <Button
                                    type="button"
                                    disabled={
                                        assignLoading ||
                                        !canAssign ||
                                        assignUserId === null
                                    }
                                    onClick={() => void handleSaveUserRoles()}
                                >
                                    {t('save_assignment')}
                                </Button>
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                                {roles.map((role) => (
                                    <label
                                        key={`assign-${role.key}`}
                                        className="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={assignRoleKeys.includes(
                                                role.key,
                                            )}
                                            disabled={!canAssign}
                                            onCheckedChange={() =>
                                                toggleAssignRole(role.key)
                                            }
                                        />
                                        <span>
                                            {roleDisplayName(role, t)}{' '}
                                            <span className="text-xs text-muted-foreground">
                                                ({role.key})
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </PermissionGate>
                </div>
            )}
        </AdminPageShell>
    );
}
