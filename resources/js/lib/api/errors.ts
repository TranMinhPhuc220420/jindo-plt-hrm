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
