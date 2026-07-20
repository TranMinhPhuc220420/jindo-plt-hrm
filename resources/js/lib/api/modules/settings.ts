import { apiGet, apiPut } from '../client';

export type SettingsMap = Record<string, Record<string, unknown>>;

export async function getSettings(group?: string) {
    const path = group ? `/api/settings/${group}` : '/api/settings';
    const res = await apiGet<SettingsMap>(path);

    return res.data;
}

export async function updateSettings(payload: SettingsMap) {
    const res = await apiPut<SettingsMap>('/api/settings', payload);

    return res.data;
}
