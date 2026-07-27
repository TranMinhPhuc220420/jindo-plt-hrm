import type { ApiErrorBody } from './types';

export class ApiError extends Error {
    status: number;
    errorCode?: string;
    errors: Record<string, string[]>;
    meta: Record<string, unknown>;

    constructor(
        message: string,
        status: number,
        errorCode?: string,
        errors: Record<string, string[]> = {},
        meta: Record<string, unknown> = {},
    ) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errorCode = errorCode;
        this.errors = errors;
        this.meta = meta;
    }

    get fieldErrors(): Record<string, string> {
        return Object.fromEntries(
            Object.entries(this.errors).map(([field, messages]) => [
                field,
                messages[0] ?? '',
            ]),
        );
    }
}

export function normalizeError(
    status: number,
    body: unknown,
    fallbackMessage = 'Request failed.',
): ApiError {
    if (body && typeof body === 'object' && 'success' in body) {
        const errorBody = body as ApiErrorBody;

        return new ApiError(
            errorBody.message || fallbackMessage,
            status,
            errorBody.error_code,
            errorBody.errors ?? {},
            errorBody.meta ?? {},
        );
    }

    return new ApiError(fallbackMessage, status);
}

export type PunchFailureKind =
    'domain' | 'unavailable' | 'server' | 'timeout' | 'network' | 'unknown';

export function classifyPunchError(err: unknown): PunchFailureKind {
    if (err instanceof DOMException && err.name === 'AbortError') {
        return 'timeout';
    }

    if (err instanceof Error && err.name === 'TimeoutError') {
        return 'timeout';
    }

    if (err instanceof TypeError) {
        return 'network';
    }

    if (!(err instanceof ApiError)) {
        return 'unknown';
    }

    if (
        err.errorCode === 'SERVICE_UNAVAILABLE' ||
        err.errorCode === 'BAD_GATEWAY' ||
        err.errorCode === 'TOO_MANY_REQUESTS' ||
        err.status === 503 ||
        err.status === 502 ||
        err.status === 429
    ) {
        return 'unavailable';
    }

    if (err.errorCode === 'SERVER_ERROR' || err.status >= 500) {
        return 'server';
    }

    if (err.status === 408) {
        return 'timeout';
    }

    if (err.status >= 400 && err.status < 500) {
        return 'domain';
    }

    return 'unknown';
}

export function isRetryablePunchError(err: unknown): boolean {
    const kind = classifyPunchError(err);

    return (
        kind === 'unavailable' ||
        kind === 'server' ||
        kind === 'timeout' ||
        kind === 'network'
    );
}

export function punchErrorToastKey(
    kind: PunchFailureKind,
):
    | 'index.toast_unavailable'
    | 'index.toast_server'
    | 'index.toast_timeout'
    | 'index.toast_network'
    | 'index.toast_error' {
    switch (kind) {
        case 'unavailable':
            return 'index.toast_unavailable';
        case 'server':
            return 'index.toast_server';
        case 'timeout':
            return 'index.toast_timeout';
        case 'network':
            return 'index.toast_network';
        default:
            return 'index.toast_error';
    }
}
