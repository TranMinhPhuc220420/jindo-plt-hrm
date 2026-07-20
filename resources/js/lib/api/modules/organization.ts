import { apiDelete, apiGet, apiPatch, apiPost } from '../client';

export type Company = {
    id: number;
    name: string;
    code: string;
    legal_name: string | null;
    tax_code: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    is_active: boolean;
};

export type Branch = {
    id: number;
    company_id: number;
    name: string;
    code: string;
    address: string | null;
    is_active: boolean;
};

export type Department = {
    id: number;
    company_id: number;
    branch_id: number;
    name: string;
    code: string;
    is_active: boolean;
};

export type Team = {
    id: number;
    company_id: number;
    department_id: number;
    name: string;
    code: string;
    is_active: boolean;
};

export type Position = {
    id: number;
    company_id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
};

export type OrganizationTree = {
    company: { id: number; name: string; code: string };
    branches: Array<{
        id: number;
        name: string;
        code: string;
        is_active: boolean;
        departments: Array<{
            id: number;
            name: string;
            code: string;
            is_active: boolean;
            teams: Array<{
                id: number;
                name: string;
                code: string;
                is_active: boolean;
            }>;
        }>;
    }>;
    positions: Array<{
        id: number;
        name: string;
        code: string;
        is_active: boolean;
    }>;
};

export async function getCurrentCompany() {
    const res = await apiGet<Company>('/api/companies/current');

    return res.data;
}

export async function updateCurrentCompany(
    payload: Partial<
        Pick<Company, 'name' | 'legal_name' | 'tax_code' | 'email' | 'phone' | 'address'>
    >,
) {
    const res = await apiPatch<Company>('/api/companies/current', payload);

    return res.data;
}

export async function getOrganizationTree() {
    const res = await apiGet<OrganizationTree>('/api/organization/tree');

    return res.data;
}

export async function createBranch(payload: {
    name: string;
    code: string;
    address?: string;
}) {
    const res = await apiPost<Branch>('/api/branches', payload);

    return res.data;
}

export async function updateBranch(
    id: number,
    payload: Partial<{ name: string; code: string; address: string; is_active: boolean }>,
) {
    const res = await apiPatch<Branch>(`/api/branches/${id}`, payload);

    return res.data;
}

export async function deleteBranch(id: number) {
    await apiDelete(`/api/branches/${id}`);
}

export async function createDepartment(payload: {
    branch_id: number;
    name: string;
    code: string;
}) {
    const res = await apiPost<Department>('/api/departments', payload);

    return res.data;
}

export async function updateDepartment(
    id: number,
    payload: Partial<{ name: string; code: string; branch_id: number; is_active: boolean }>,
) {
    const res = await apiPatch<Department>(`/api/departments/${id}`, payload);

    return res.data;
}

export async function deleteDepartment(id: number) {
    await apiDelete(`/api/departments/${id}`);
}

export async function createTeam(payload: {
    department_id: number;
    name: string;
    code: string;
}) {
    const res = await apiPost<Team>('/api/teams', payload);

    return res.data;
}

export async function updateTeam(
    id: number,
    payload: Partial<{
        name: string;
        code: string;
        department_id: number;
        is_active: boolean;
    }>,
) {
    const res = await apiPatch<Team>(`/api/teams/${id}`, payload);

    return res.data;
}

export async function deleteTeam(id: number) {
    await apiDelete(`/api/teams/${id}`);
}

export async function createPosition(payload: {
    name: string;
    code: string;
    description?: string;
}) {
    const res = await apiPost<Position>('/api/positions', payload);

    return res.data;
}

export async function updatePosition(
    id: number,
    payload: Partial<{
        name: string;
        code: string;
        description: string;
        is_active: boolean;
    }>,
) {
    const res = await apiPatch<Position>(`/api/positions/${id}`, payload);

    return res.data;
}

export async function deletePosition(id: number) {
    await apiDelete(`/api/positions/${id}`);
}
