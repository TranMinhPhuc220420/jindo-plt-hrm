import { ApiError, normalizeError } from './errors';
import type { ApiSuccess } from './types';

type RequestOptions = {
    method?: string;
    body?: unknown;
    headers?: Record<string, string>;
    signal?: AbortSignal;
    /** Skip CSRF bootstrap (rare). */
    skipCsrf?: boolean;
    /** Do not invoke the global 401 handler (e.g. guest bootstrap of /api/me). */
    skipUnauthorized?: boolean;
};

type UnauthorizedHandler = () => void;

let onUnauthorized: UnauthorizedHandler | null = null;

export function setUnauthorizedHandler(
    handler: UnauthorizedHandler | null,
): void {
    onUnauthorized = handler;
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(
        new RegExp(
            `(?:^|; )${name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1')}=([^;]*)`,
        ),
    );

    return match ? decodeURIComponent(match[1]) : null;
}

let csrfPromise: Promise<void> | null = null;

/**
 * Sanctum SPA: establish CSRF cookie before mutating requests.
 * Call once before login / other POSTs from a cold browser session.
 */
export async function ensureCsrfCookie(): Promise<void> {
    if (!csrfPromise) {
        csrfPromise = fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            if (!response.ok) {
                csrfPromise = null;

                throw new ApiError(
                    'Unable to initialize CSRF cookie.',
                    response.status,
                );
            }
        });
    }

    await csrfPromise;
}

async function parseBody(response: Response): Promise<unknown> {
    const text = await response.text();

    if (!text) {
        return null;
    }

    try {
        return JSON.parse(text) as unknown;
    } catch {
        return text;
    }
}

export async function apiRequest<T>(
    path: string,
    options: RequestOptions = {},
): Promise<ApiSuccess<T>> {
    const method = (options.method ?? 'GET').toUpperCase();
    const isMutating = !['GET', 'HEAD', 'OPTIONS'].includes(method);

    if (isMutating && !options.skipCsrf) {
        await ensureCsrfCookie();
    }

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };

    if (isMutating) {
        const xsrf = getCookie('XSRF-TOKEN');

        if (xsrf) {
            headers['X-XSRF-TOKEN'] = xsrf;
        }
    }

    if (options.body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(path.startsWith('/') ? path : `/${path}`, {
        method,
        credentials: 'same-origin',
        headers,
        signal: options.signal,
        body:
            options.body === undefined
                ? undefined
                : JSON.stringify(options.body),
    });

    const body = await parseBody(response);

    if (!response.ok) {
        const error = normalizeError(response.status, body);

        if (response.status === 401 && !options.skipUnauthorized) {
            onUnauthorized?.();
        }

        throw error;
    }

    if (
        body &&
        typeof body === 'object' &&
        'success' in body &&
        (body as ApiSuccess<T>).success === true
    ) {
        return body as ApiSuccess<T>;
    }

    throw new ApiError('Unexpected API response shape.', response.status);
}

export function apiGet<T>(
    path: string,
    options?: Omit<RequestOptions, 'method' | 'body'>,
) {
    return apiRequest<T>(path, { ...options, method: 'GET' });
}

export function apiPost<T>(
    path: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>,
) {
    return apiRequest<T>(path, { ...options, method: 'POST', body });
}

export function apiPut<T>(
    path: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>,
) {
    return apiRequest<T>(path, { ...options, method: 'PUT', body });
}

export function apiPatch<T>(
    path: string,
    body?: unknown,
    options?: Omit<RequestOptions, 'method' | 'body'>,
) {
    return apiRequest<T>(path, { ...options, method: 'PATCH', body });
}

export function apiDelete<T>(
    path: string,
    options?: Omit<RequestOptions, 'method' | 'body'>,
) {
    return apiRequest<T>(path, { ...options, method: 'DELETE' });
}
