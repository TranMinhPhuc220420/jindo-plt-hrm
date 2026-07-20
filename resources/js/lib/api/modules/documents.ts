import { apiDelete, apiGet, apiPatch, ensureCsrfCookie } from '../client';
import { normalizeError } from '../errors';
import type { PaginationMeta } from '../types';

export type DocumentOwnerType = 'company' | 'employee' | 'candidate';
export type DocumentCategory =
    'policy' | 'template' | 'contract' | 'certificate' | 'other';

export type Document = {
    id: number;
    company_id: number;
    owner_type: DocumentOwnerType;
    owner_id: number | null;
    category: string;
    title: string;
    original_name: string;
    mime_type: string | null;
    size: number | null;
    uploaded_by: number | null;
    created_at: string | null;
    updated_at: string | null;
};

export async function listDocuments(
    params: {
        owner_type?: DocumentOwnerType;
        owner_id?: number;
        category?: string;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.owner_type) {
        query.set('owner_type', params.owner_type);
    }

    if (params.owner_id) {
        query.set('owner_id', String(params.owner_id));
    }

    if (params.category) {
        query.set('category', params.category);
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Document[]>(`/api/documents${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getDocument(id: number) {
    const res = await apiGet<Document>(`/api/documents/${id}`);

    return res.data;
}

export async function uploadDocument(payload: {
    file: File;
    owner_type: DocumentOwnerType;
    owner_id?: number | null;
    category?: string;
    title?: string;
}): Promise<Document> {
    await ensureCsrfCookie();

    const form = new FormData();
    form.append('file', payload.file);
    form.append('owner_type', payload.owner_type);

    if (payload.owner_id != null) {
        form.append('owner_id', String(payload.owner_id));
    }

    if (payload.category) {
        form.append('category', payload.category);
    }

    if (payload.title) {
        form.append('title', payload.title);
    }

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch('/api/documents', {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: form,
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
        throw normalizeError(response.status, body);
    }

    return (body as { data: Document }).data;
}

export async function updateDocument(
    id: number,
    payload: { category?: string; title?: string },
) {
    const res = await apiPatch<Document>(`/api/documents/${id}`, payload);

    return res.data;
}

export async function deleteDocument(id: number): Promise<void> {
    await apiDelete(`/api/documents/${id}`);
}

export async function downloadDocument(doc: Document): Promise<void> {
    await ensureCsrfCookie();

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'application/octet-stream, application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch(`/api/documents/${doc.id}/download`, {
        method: 'GET',
        credentials: 'same-origin',
        headers,
    });

    if (!response.ok) {
        const body = await response.json().catch(() => null);

        throw normalizeError(response.status, body);
    }

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = doc.original_name || `document-${doc.id}`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}
