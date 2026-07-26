import { apiDelete, apiGet, apiPatch, apiPost, apiPut } from '../client';
import type { PaginationMeta } from '../types';

export type ShiftKind = 'standard' | 'rotating' | 'night' | 'flexible';

export type Shift = {
    id: number;
    company_id: number;
    name: string;
    code: string;
    start_time: string;
    end_time: string;
    break_minutes: number;
    kind: ShiftKind;
    is_night: boolean;
    is_flexible: boolean;
    is_active: boolean;
};

export type ShiftAssignment = {
    id: number;
    company_id: number;
    employee_id: number;
    shift_id: number;
    start_date: string;
    end_date: string | null;
    shift?: Shift | null;
    employee?: { id: number; code: string; full_name: string } | null;
};

export type WorkingCalendarRestKind = 'none' | 'weekend' | 'holiday';

export type WorkingCalendarLeave = {
    request_id: number;
    leave_type_name: string;
    is_paid: boolean;
    unit: 'day' | 'half_day' | 'hour';
    coverage: 'full' | 'am' | 'pm' | 'hours';
    start_at: string | null;
    end_at: string | null;
};

export type WorkingCalendarDay = {
    date: string;
    shift_id: number | null;
    shift_name: string | null;
    start_time: string | null;
    end_time: string | null;
    is_holiday: boolean;
    rest_kind: WorkingCalendarRestKind;
    holiday_name: string | null;
    leave: WorkingCalendarLeave | null;
};

export type OvertimeRule = {
    id: number;
    company_id: number;
    code: string;
    name: string;
    applies_after_minutes: number;
    allow_before_shift: boolean;
    night_ot_enabled: boolean;
    is_active: boolean;
};

export type ShiftListParams = {
    search?: string;
    kind?: string;
    is_active?: boolean | string;
    page?: number;
    per_page?: number;
};

export async function listShifts(params: ShiftListParams = {}) {
    const query = new URLSearchParams();

    if (params.search) {
        query.set('search', params.search);
    }

    if (params.kind) {
        query.set('kind', params.kind);
    }

    if (params.is_active !== undefined && params.is_active !== '') {
        query.set('is_active', String(params.is_active));
    }

    if (params.page) {
        query.set('page', String(params.page));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Shift[]>(`/api/shifts${suffix}`);

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getShift(id: number) {
    const res = await apiGet<Shift>(`/api/shifts/${id}`);

    return res.data;
}

export async function createShift(payload: Record<string, unknown>) {
    const res = await apiPost<Shift>('/api/shifts', payload);

    return res.data;
}

export async function updateShift(
    id: number,
    payload: Record<string, unknown>,
) {
    const res = await apiPatch<Shift>(`/api/shifts/${id}`, payload);

    return res.data;
}

export async function deleteShift(id: number) {
    await apiDelete(`/api/shifts/${id}`);
}

export async function listShiftAssignments(
    params: {
        employee_id?: number;
        shift_id?: number;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.shift_id) {
        query.set('shift_id', String(params.shift_id));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<ShiftAssignment[]>(
        `/api/shift-assignments${suffix}`,
    );

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function createShiftAssignment(payload: Record<string, unknown>) {
    const res = await apiPost<ShiftAssignment>(
        '/api/shift-assignments',
        payload,
    );

    return res.data;
}

export async function deleteShiftAssignment(id: number) {
    await apiDelete(`/api/shift-assignments/${id}`);
}

export async function getWorkingCalendar(params: {
    employee_id: number;
    date_from: string;
    date_to: string;
}) {
    const query = new URLSearchParams({
        employee_id: String(params.employee_id),
        date_from: params.date_from,
        date_to: params.date_to,
    });
    const res = await apiGet<WorkingCalendarDay[]>(
        `/api/working-calendar?${query.toString()}`,
    );

    return res.data;
}

export async function listOvertimeRules() {
    const res = await apiGet<OvertimeRule[]>('/api/overtime-rules');

    return res.data;
}

export async function replaceOvertimeRules(rules: Record<string, unknown>[]) {
    const res = await apiPut<OvertimeRule[]>('/api/overtime-rules', { rules });

    return res.data;
}
