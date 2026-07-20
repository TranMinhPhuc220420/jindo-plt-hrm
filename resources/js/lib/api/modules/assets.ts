import { apiGet, apiPatch, apiPost } from '../client';
import type { PaginationMeta } from '../types';

export type AssetStatus =
    | 'available'
    | 'assigned'
    | 'maintenance'
    | 'retired'
    | 'lost';

export type Asset = {
    id: number;
    company_id: number;
    code: string;
    name: string;
    category: string | null;
    status: AssetStatus;
    serial_number: string | null;
    assigned_to: number | null;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type AssetAssignment = {
    id: number;
    company_id: number;
    asset_id: number;
    employee_id: number;
    status: string;
    assigned_at: string | null;
    assigned_by: number | null;
    returned_at: string | null;
    return_condition: string | null;
    note: string | null;
    created_at: string | null;
};

export type AssetMaintenance = {
    id: number;
    company_id: number;
    asset_id: number;
    description: string;
    status: string;
    cost: string | null;
    scheduled_at: string | null;
    completed_at: string | null;
    note: string | null;
    created_at: string | null;
};

export type AssetDamageReport = {
    id: number;
    company_id: number;
    asset_id: number;
    description: string;
    reported_at: string | null;
    reported_by: number | null;
    document_ids: number[] | null;
    created_at: string | null;
};

export async function listAssets(
    params: { status?: string; category?: string; search?: string; per_page?: number } = {},
) {
    const query = new URLSearchParams();
    if (params.status) {
        query.set('status', params.status);
    }
    if (params.category) {
        query.set('category', params.category);
    }
    if (params.search) {
        query.set('search', params.search);
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Asset[]>(`/api/assets${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getAsset(id: number) {
    const res = await apiGet<Asset>(`/api/assets/${id}`);

    return res.data;
}

export async function createAsset(payload: {
    code: string;
    name: string;
    category?: string;
    status?: AssetStatus;
    serial_number?: string;
    notes?: string;
}) {
    const res = await apiPost<Asset>('/api/assets', payload);

    return res.data;
}

export async function updateAsset(
    id: number,
    payload: Partial<{
        code: string;
        name: string;
        category: string;
        status: AssetStatus;
        serial_number: string;
        notes: string;
    }>,
) {
    const res = await apiPatch<Asset>(`/api/assets/${id}`, payload);

    return res.data;
}

export async function retireAsset(id: number) {
    const res = await apiPost<Asset>(`/api/assets/${id}/retire`);

    return res.data;
}

export async function assignAsset(
    id: number,
    payload: { employee_id: number; assigned_at?: string; note?: string },
) {
    const res = await apiPost<AssetAssignment>(`/api/assets/${id}/assign`, payload);

    return res.data;
}

export async function returnAsset(
    id: number,
    payload: { returned_at?: string; condition?: string; note?: string } = {},
) {
    const res = await apiPost<AssetAssignment>(`/api/assets/${id}/return`, payload);

    return res.data;
}

export async function listMaintenances(id: number) {
    const res = await apiGet<AssetMaintenance[]>(`/api/assets/${id}/maintenances`);

    return res.data ?? [];
}

export async function createMaintenance(
    id: number,
    payload: {
        description: string;
        status?: string;
        cost?: number | string;
        scheduled_at?: string;
        completed_at?: string;
        note?: string;
    },
) {
    const res = await apiPost<AssetMaintenance>(
        `/api/assets/${id}/maintenances`,
        payload,
    );

    return res.data;
}

export async function reportDamage(
    id: number,
    payload: { description: string; reported_at?: string; document_ids?: number[] },
) {
    const res = await apiPost<AssetDamageReport>(
        `/api/assets/${id}/damage-reports`,
        payload,
    );

    return res.data;
}

export async function listAssignments(
    params: { asset_id?: number; employee_id?: number; status?: string; per_page?: number } = {},
) {
    const query = new URLSearchParams();
    if (params.asset_id) {
        query.set('asset_id', String(params.asset_id));
    }
    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }
    if (params.status) {
        query.set('status', params.status);
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<AssetAssignment[]>(`/api/asset-assignments${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}
