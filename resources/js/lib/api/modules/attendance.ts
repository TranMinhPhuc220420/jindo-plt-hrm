import { apiGet, apiPost, ensureCsrfCookie } from '../client';
import { normalizeError } from '../errors';
import type { PaginationMeta } from '../types';

export type AttendanceStatus =
    'open' | 'pending' | 'approved' | 'rejected' | 'locked';

export type AttendanceEvidence = {
    id: number;
    punch_type: 'check_in' | 'check_out';
    latitude: number;
    longitude: number;
    accuracy_meters: number | null;
    address: string;
    has_photo: boolean;
    photo_url: string;
    captured_at: string | null;
};

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
    evidences?: AttendanceEvidence[];
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

export type PunchEvidenceInput = {
    latitude: number;
    longitude: number;
    accuracy_meters?: number | null;
    address: string;
    photo: File;
    worked_at?: string;
    note?: string;
    captured_at?: string;
    /** Required UUID — reuse on retry / offline sync for the same attempt. */
    idempotencyKey: string;
};

const PUNCH_TIMEOUT_MS = 45_000;

async function postPunch(
    path: string,
    payload: PunchEvidenceInput,
): Promise<AttendanceRecord> {
    await ensureCsrfCookie();

    const form = new FormData();
    form.append('latitude', String(payload.latitude));
    form.append('longitude', String(payload.longitude));
    form.append('address', payload.address);
    form.append('photo', payload.photo);

    if (
        payload.accuracy_meters !== undefined &&
        payload.accuracy_meters !== null
    ) {
        form.append('accuracy_meters', String(payload.accuracy_meters));
    }

    if (payload.worked_at) {
        form.append('worked_at', payload.worked_at);
    } else if (payload.captured_at) {
        // Align punch work_date with evidence capture time (offline sync / SPA).
        form.append('worked_at', payload.captured_at);
    }

    if (payload.note) {
        form.append('note', payload.note);
    }

    if (payload.captured_at) {
        form.append('captured_at', payload.captured_at);
    }

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Idempotency-Key': payload.idempotencyKey,
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: form,
        signal: AbortSignal.timeout(PUNCH_TIMEOUT_MS),
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
        throw normalizeError(response.status, body);
    }

    return (body as { data: AttendanceRecord }).data;
}

export async function checkIn(payload: PunchEvidenceInput) {
    return postPunch('/api/attendance/check-in', payload);
}

export async function checkOut(payload: PunchEvidenceInput) {
    return postPunch('/api/attendance/check-out', payload);
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

export async function bulkApproveRecords(ids: number[]) {
    const res = await apiPost<{
        approved_count: number;
        approved_ids: number[];
        skipped_ids: number[];
    }>('/api/attendance/records/bulk-approve', { ids });

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

/**
 * Fetch a private punch photo with session credentials (same pattern as documents).
 * Returns an object URL the caller must revoke.
 */
export async function fetchEvidencePhotoObjectUrl(
    recordId: number,
    punchType: 'check_in' | 'check_out',
): Promise<string> {
    await ensureCsrfCookie();

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'image/*, application/octet-stream, application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch(
        `/api/attendance/records/${recordId}/evidences/${punchType}/photo`,
        {
            method: 'GET',
            credentials: 'same-origin',
            headers,
        },
    );

    if (!response.ok) {
        const body = await response.json().catch(() => null);

        throw normalizeError(response.status, body);
    }

    const blob = await response.blob();

    return URL.createObjectURL(blob);
}
