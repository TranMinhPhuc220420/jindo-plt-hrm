import { apiGet, apiPost } from '../client';
import type { PaginationMeta } from '../types';

export type AttendanceStatus =
    'open' | 'pending' | 'approved' | 'rejected' | 'locked';

export type AttendanceRecord = {
    id: number;
    company_id: number;
    employee_id: number;
    work_date: string;
    check_in_at: string | null;
    check_out_at: string | null;
    worked_minutes: number;
    late_minutes: number;
    early_leave_minutes: number;
    overtime_minutes: number;
    break_minutes: number;
    status: AttendanceStatus;
    source: string;
    note: string | null;
    employee?: { id: number; code: string; full_name: string } | null;
};

export type AttendanceCorrection = {
    id: number;
    attendance_record_id: number;
    employee_id: number;
    proposed_check_in_at: string | null;
    proposed_check_out_at: string | null;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    review_note: string | null;
    record?: AttendanceRecord | null;
    employee?: { id: number; code: string; full_name: string } | null;
};

export type AttendanceSummary = {
    employee_id: number;
    period_start: string;
    period_end: string;
    worked_minutes: number;
    late_minutes: number;
    overtime_minutes: number;
    days_present: number;
};

export async function checkIn(payload: Record<string, unknown> = {}) {
    const res = await apiPost<AttendanceRecord>(
        '/api/attendance/check-in',
        payload,
    );

    return res.data;
}

export async function checkOut(payload: Record<string, unknown> = {}) {
    const res = await apiPost<AttendanceRecord>(
        '/api/attendance/check-out',
        payload,
    );

    return res.data;
}

export async function listRecords(
    params: {
        employee_id?: number;
        date_from?: string;
        date_to?: string;
        status?: string;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.date_from) {
        query.set('date_from', params.date_from);
    }

    if (params.date_to) {
        query.set('date_to', params.date_to);
    }

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<AttendanceRecord[]>(
        `/api/attendance/records${suffix}`,
    );

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function approveRecord(id: number) {
    const res = await apiPost<AttendanceRecord>(
        `/api/attendance/records/${id}/approve`,
    );

    return res.data;
}

export async function listCorrections(
    params: {
        status?: string;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<AttendanceCorrection[]>(
        `/api/attendance/corrections${suffix}`,
    );

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function requestCorrection(payload: Record<string, unknown>) {
    const res = await apiPost<AttendanceCorrection>(
        '/api/attendance/corrections',
        payload,
    );

    return res.data;
}

export async function approveCorrection(id: number) {
    const res = await apiPost<AttendanceCorrection>(
        `/api/attendance/corrections/${id}/approve`,
    );

    return res.data;
}

export async function rejectCorrection(
    id: number,
    payload: { review_note?: string } = {},
) {
    const res = await apiPost<AttendanceCorrection>(
        `/api/attendance/corrections/${id}/reject`,
        payload,
    );

    return res.data;
}

export async function getSummary(params: {
    employee_id: number;
    period_start: string;
    period_end: string;
}) {
    const query = new URLSearchParams({
        employee_id: String(params.employee_id),
        period_start: params.period_start,
        period_end: params.period_end,
    });
    const res = await apiGet<AttendanceSummary>(
        `/api/attendance/summary?${query.toString()}`,
    );

    return res.data;
}
