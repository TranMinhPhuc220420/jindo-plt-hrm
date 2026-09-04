import {
    apiDelete,
    apiGet,
    apiPatch,
    apiPost,
    apiPut,
    ensureCsrfCookie,
} from '../client';
import { normalizeError } from '../errors';
import type { PaginationMeta } from '../types';

export type EmployeeStatus =
    'probation' | 'active' | 'suspended' | 'resigned' | 'archived';

export type Employee = {
    id: number;
    company_id: number;
    code: string;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string | null;
    phone: string | null;
    user_id: number | null;
    branch_id: number | null;
    department_id: number | null;
    team_id: number | null;
    position_id: number | null;
    manager_id: number | null;
    hired_at: string | null;
    terminated_at: string | null;
    status: EmployeeStatus;
    allowed_next_statuses?: EmployeeStatus[];
    avatar_url?: string | null;
    outstanding_assets?: Array<{
        id: number;
        code: string | null;
        name: string | null;
    }>;
    outstanding_assets_count?: number;
    department?: { id: number; name: string; code: string } | null;
    position?: { id: number; name: string; code: string } | null;
    branch?: { id: number; name: string; code: string } | null;
};

export type EmployeeListParams = {
    search?: string;
    status?: string;
    department_id?: number | '';
    page?: number;
    per_page?: number;
};

export async function listEmployees(params: EmployeeListParams = {}) {
    const query = new URLSearchParams();

    if (params.search) {
        query.set('search', params.search);
    }

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.department_id) {
        query.set('department_id', String(params.department_id));
    }

    if (params.page) {
        query.set('page', String(params.page));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Employee[]>(`/api/employees${suffix}`);

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getEmployee(id: number) {
    const res = await apiGet<Employee>(`/api/employees/${id}`);

    return res.data;
}

export async function createEmployee(payload: Record<string, unknown>) {
    const res = await apiPost<Employee>('/api/employees', payload);

    return res.data;
}

export async function updateEmployee(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPatch<Employee>(`/api/employees/${id}`, payload);

    return res.data;
}

export async function updateEmployeePassword(
    id: number,
    payload:
        | { use_default: true }
        | { password: string; password_confirmation: string },
) {
    const res = await apiPut<Employee>(
        `/api/employees/${id}/password`,
        payload,
    );

    return res.data;
}

export async function changeEmployeeStatus(
    id: number,
    payload: {
        status: EmployeeStatus;
        reason?: string;
        effective_on?: string;
        confirm_asset_return?: boolean;
    },
) {
    const res = await apiPost<Employee>(`/api/employees/${id}/status`, payload);

    return res.data;
}

export async function deleteEmployee(id: number) {
    await apiDelete(`/api/employees/${id}`);
}

export async function getBankAccount(id: number) {
    const res = await apiGet<Record<string, unknown> | null>(
        `/api/employees/${id}/bank-account`,
    );

    return res.data;
}

export async function updateBankAccount(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPut<Record<string, unknown>>(
        `/api/employees/${id}/bank-account`,
        payload,
    );

    return res.data;
}

export async function getTaxProfile(id: number) {
    const res = await apiGet<Record<string, unknown> | null>(
        `/api/employees/${id}/tax-profile`,
    );

    return res.data;
}

export async function updateTaxProfile(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPut<Record<string, unknown>>(
        `/api/employees/${id}/tax-profile`,
        payload,
    );

    return res.data;
}

export async function getInsurance(id: number) {
    const res = await apiGet<Record<string, unknown> | null>(
        `/api/employees/${id}/insurance`,
    );

    return res.data;
}

export async function updateInsurance(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPut<Record<string, unknown>>(
        `/api/employees/${id}/insurance`,
        payload,
    );

    return res.data;
}

export async function replaceEmergencyContacts(
    id: number,
    contacts: Array<Record<string, unknown>>,
) {
    const res = await apiPut<Array<Record<string, unknown>>>(
        `/api/employees/${id}/emergency-contacts`,
        { contacts },
    );

    return res.data;
}

export async function listEmergencyContacts(id: number) {
    const res = await apiGet<Array<Record<string, unknown>>>(
        `/api/employees/${id}/emergency-contacts`,
    );

    return res.data;
}

export async function listContracts(id: number) {
    const res = await apiGet<Array<Record<string, unknown>>>(
        `/api/employees/${id}/contracts`,
    );

    return res.data;
}

export async function createContract(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPost<Record<string, unknown>>(
        `/api/employees/${id}/contracts`,
        payload,
    );

    return res.data;
}

export async function uploadEmployeeAvatar(
    id: number,
    file: File,
): Promise<Employee> {
    await ensureCsrfCookie();

    const form = new FormData();
    form.append('avatar', file);

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch(`/api/employees/${id}/avatar`, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: form,
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
        throw normalizeError(response.status, body);
    }

    return (body as { data: Employee }).data;
}

export async function deleteEmployeeAvatar(id: number): Promise<Employee> {
    const res = await apiDelete<Employee>(`/api/employees/${id}/avatar`);

    return res.data;
}
