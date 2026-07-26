import { apiDelete, apiGet, apiPost, apiPut, ensureCsrfCookie } from '../client';
import { normalizeError } from '../errors';
import type { AuthPayload, LoginPayload } from '../types';

export type LoginInput = {
    email: string;
    password: string;
    remember?: boolean;
};

export async function login(input: LoginInput): Promise<LoginPayload> {
    const response = await apiPost<LoginPayload>('/api/auth/login', input);

    return response.data;
}

export async function logout(): Promise<void> {
    await apiPost<null>('/api/auth/logout');
}

export async function getMe(): Promise<AuthPayload> {
    const response = await apiGet<AuthPayload>('/api/me');

    return response.data;
}

export async function updateLocale(
    locale: string | null,
): Promise<AuthPayload> {
    const response = await apiPut<AuthPayload>('/api/me/locale', { locale });

    return response.data;
}

export async function forgotPassword(
    email: string,
): Promise<string | undefined> {
    const response = await apiPost<null>('/api/auth/forgot-password', {
        email,
    });

    return response.message;
}

export async function resetPassword(input: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}): Promise<string | undefined> {
    const response = await apiPost<null>('/api/auth/reset-password', input);

    return response.message;
}

export async function challengeTwoFactor(input: {
    code?: string;
    recovery_code?: string;
}): Promise<AuthPayload> {
    const response = await apiPost<AuthPayload>(
        '/api/auth/two-factor/challenge',
        input,
    );

    return response.data;
}

export async function uploadMyAvatar(file: File): Promise<AuthPayload> {
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

    const response = await fetch('/api/me/avatar', {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: form,
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
        throw normalizeError(response.status, body);
    }

    return (body as { data: AuthPayload }).data;
}

export async function deleteMyAvatar(): Promise<AuthPayload> {
    const response = await apiDelete<AuthPayload>('/api/me/avatar');

    return response.data;
}
