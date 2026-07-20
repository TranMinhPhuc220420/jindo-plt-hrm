import { apiDelete, apiGet, apiPost, apiPut } from '../client';
import type { PaginationMeta } from '../types';

export type Permission = {
    id: number;
    key: string;
    name: string;
    group: string | null;
    description: string | null;
};

export type Role = {
    id: number;
    key: string;
    name: string;
    description: string | null;
    is_system: boolean;
    permissions?: Permission[];
};

export async function listPermissions() {
    const res = await apiGet<Permission[]>('/api/permissions');

    return res.data;
}

export async function listRoles() {
    const res = await apiGet<Role[]>('/api/roles');

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function createRole(payload: {
    key: string;
    name: string;
    description?: string;
}) {
    const res = await apiPost<Role>('/api/roles', payload);

    return res.data;
}

export async function syncRolePermissions(
    roleId: number,
    permissions: string[],
) {
    const res = await apiPut<Role>(`/api/roles/${roleId}/permissions`, {
        permissions,
    });

    return res.data;
}

export async function deleteRole(roleId: number) {
    await apiDelete(`/api/roles/${roleId}`);
}

export async function getUserRoles(userId: number) {
    const res = await apiGet<Role[]>(`/api/users/${userId}/roles`);

    return res.data;
}

export async function syncUserRoles(userId: number, roles: string[]) {
    const res = await apiPut<Role[]>(`/api/users/${userId}/roles`, { roles });

    return res.data;
}
