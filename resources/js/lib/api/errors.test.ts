import { describe, expect, it } from 'vitest';
import { ApiError, normalizeError } from '@/lib/api/errors';

describe('ApiError.fieldErrors', () => {
    it('maps the first validation message per field for 422 forms', () => {
        const error = new ApiError(
            'Validation failed.',
            422,
            'VALIDATION_FAILED',
            {
                email: ['The email field is required.', 'Must be valid.'],
                password: ['Too short.'],
            },
        );

        expect(error.fieldErrors).toEqual({
            email: 'The email field is required.',
            password: 'Too short.',
        });
    });

    it('normalizeError preserves field errors from API envelopes', () => {
        const error = normalizeError(422, {
            success: false,
            message: 'Invalid.',
            error_code: 'VALIDATION_FAILED',
            errors: { code: ['Already taken.'] },
            meta: {},
        });

        expect(error).toBeInstanceOf(ApiError);
        expect(error.fieldErrors).toEqual({ code: 'Already taken.' });
        expect(error.errorCode).toBe('VALIDATION_FAILED');
    });
});
