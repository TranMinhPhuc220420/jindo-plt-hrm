import type { TFunction } from 'i18next';

/**
 * Localize a leave type by stable API/DB `code` (e.g. ANNUAL).
 * Falls back to the API display name, then the code.
 */
export function leaveTypeLabel(
    t: TFunction,
    code: string | null | undefined,
    fallbackName?: string | null,
): string {
    if (code) {
        return t(`leave_types.${code}`, {
            ns: 'leave',
            defaultValue: fallbackName || code,
        });
    }

    if (fallbackName) {
        return fallbackName;
    }

    return t('empty_value', { ns: 'common' });
}
