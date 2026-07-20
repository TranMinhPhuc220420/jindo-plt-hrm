import { apiDelete, apiGet, apiPost, apiPut } from '../client';
import type { PaginationMeta } from '../types';

export type Notification = {
    id: number;
    type: string;
    title: string;
    body: string | null;
    data: Record<string, unknown> | null;
    read_at: string | null;
    created_at: string | null;
};

export type NotificationPreferences = {
    email: boolean;
    push: boolean;
    system: boolean;
    categories: Record<string, unknown>;
};

export async function listNotifications(
    params: { unread_only?: boolean; type?: string; per_page?: number } = {},
) {
    const query = new URLSearchParams();
    if (params.unread_only) {
        query.set('unread_only', '1');
    }
    if (params.type) {
        query.set('type', params.type);
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Notification[]>(`/api/notifications${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function unreadCount(): Promise<number> {
    const res = await apiGet<{ unread_count: number }>(
        '/api/notifications/unread-count',
    );

    return res.data?.unread_count ?? 0;
}

export async function markRead(id: number) {
    const res = await apiPost<Notification>(`/api/notifications/${id}/read`);

    return res.data;
}

export async function markAllRead(): Promise<number> {
    const res = await apiPost<{ marked: number }>(
        '/api/notifications/read-all',
    );

    return res.data?.marked ?? 0;
}

export async function deleteNotification(id: number): Promise<void> {
    await apiDelete(`/api/notifications/${id}`);
}

export async function getPreferences() {
    const res = await apiGet<NotificationPreferences>(
        '/api/notification-preferences',
    );

    return res.data;
}

export async function updatePreferences(payload: {
    email?: boolean;
    push?: boolean;
    system?: boolean;
    categories?: Record<string, unknown> | null;
}) {
    const res = await apiPut<NotificationPreferences>(
        '/api/notification-preferences',
        payload,
    );

    return res.data;
}

export async function broadcastNotification(payload: {
    title: string;
    body?: string | null;
}): Promise<number> {
    const res = await apiPost<{ sent: number }>(
        '/api/notifications/broadcast',
        payload,
    );

    return res.data?.sent ?? 0;
}
