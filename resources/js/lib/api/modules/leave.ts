import { apiDelete, apiGet, apiPatch, apiPost, apiPut } from '../client';
import type { PaginationMeta } from '../types';

export type LeaveType = {
    id: number;
    company_id: number;
    code: string;
    name: string;
    unit_default: 'day' | 'half_day' | 'hour';
    is_paid: boolean;
    requires_balance: boolean;
    allows_negative: boolean;
    is_active: boolean;
};

export type LeaveBalance = {
    leave_type_id: number;
    leave_type_name: string;
    leave_type_code: string;
    period_key: string;
    entitled: number;
    used: number;
    pending: number;
    remaining: number;
};

export type LeaveRequest = {
    id: number;
    company_id: number;
    employee_id: number;
    employee_code?: string | null;
    employee_name?: string | null;
    leave_type_id: number;
    leave_type_name?: string | null;
    leave_type_code?: string | null;
    unit: string;
    start_date: string;
    end_date: string;
    is_half_day: boolean;
    half_day_period: string | null;
    quantity: number;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    review_note: string | null;
};

export type Holiday = {
    id: number;
    company_id: number;
    date: string;
    name: string;
};

export type WeekendRule = {
    id: number;
    company_id: number;
    weekend_days: number[];
};

export async function listLeaveTypes(params: { per_page?: number } = {}) {
    const query = new URLSearchParams();

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<LeaveType[]>(`/api/leave-types${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function createLeaveType(payload: Record<string, unknown>) {
    const res = await apiPost<LeaveType>('/api/leave-types', payload);

    return res.data;
}

export async function updateLeaveType(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPatch<LeaveType>(`/api/leave-types/${id}`, payload);

    return res.data;
}

export async function listBalances(
    params: {
        employee_id?: number;
        year?: string | number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.year) {
        query.set('year', String(params.year));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<LeaveBalance[]>(`/api/leave-balances${suffix}`);

    return res.data ?? [];
}

export async function adjustBalance(payload: Record<string, unknown>) {
    const res = await apiPost<LeaveBalance>(
        '/api/leave-balances/adjust',
        payload,
    );

    return res.data;
}

export async function listRequests(
    params: {
        status?: string;
        employee_id?: number;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<LeaveRequest[]>(`/api/leave-requests${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function createRequest(payload: Record<string, unknown>) {
    const res = await apiPost<LeaveRequest>('/api/leave-requests', payload);

    return res.data;
}

export async function cancelRequest(id: number) {
    const res = await apiPost<LeaveRequest>(`/api/leave-requests/${id}/cancel`);

    return res.data;
}

export async function approveRequest(id: number, note?: string) {
    const res = await apiPost<LeaveRequest>(
        `/api/leave-requests/${id}/approve`,
        note ? { note } : {},
    );

    return res.data;
}

export async function rejectRequest(id: number, reason?: string) {
    const res = await apiPost<LeaveRequest>(
        `/api/leave-requests/${id}/reject`,
        reason ? { reason } : {},
    );

    return res.data;
}

export async function listHolidays(year?: string | number) {
    const suffix = year ? `?year=${year}` : '';
    const res = await apiGet<Holiday[]>(`/api/holidays${suffix}`);

    return res.data ?? [];
}

export async function createHoliday(payload: { date: string; name: string }) {
    const res = await apiPost<Holiday>('/api/holidays', payload);

    return res.data;
}

export async function deleteHoliday(id: number) {
    await apiDelete(`/api/holidays/${id}`);
}

export async function getWeekendRules() {
    const res = await apiGet<WeekendRule>('/api/weekend-rules');

    return res.data;
}

export async function updateWeekendRules(weekend_days: number[]) {
    const res = await apiPut<WeekendRule>('/api/weekend-rules', {
        weekend_days,
    });

    return res.data;
}
