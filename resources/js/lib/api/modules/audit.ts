import { apiGet } from '../client';
import type { PaginationMeta } from '../types';

export type AuditLog = {
    id: number;
    company_id: number | null;
    action: string;
    actor_type: string | null;
    actor_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    payload: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string | null;
};

export async function listAuditLogs(params?: {
    page?: number;
    per_page?: number;
    action?: string;
    subject_type?: string;
    date_from?: string;
    date_to?: string;
}) {
    const query = new URLSearchParams();

    if (params?.page) {
        query.set('page', String(params.page));
    }

    if (params?.per_page) {
        query.set('per_page', String(params.per_page));
    }

    if (params?.action) {
        query.set('action', params.action);
    }

    if (params?.subject_type) {
        query.set('subject_type', params.subject_type);
    }

    if (params?.date_from) {
        query.set('date_from', params.date_from);
    }

    if (params?.date_to) {
        query.set('date_to', params.date_to);
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<AuditLog[]>(`/api/audit-logs${suffix}`);

    return {
        data: res.data,
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getAuditLog(id: number) {
    const res = await apiGet<AuditLog>(`/api/audit-logs/${id}`);

    return res.data;
}
