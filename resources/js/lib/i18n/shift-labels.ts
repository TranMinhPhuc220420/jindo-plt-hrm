import type { TFunction } from 'i18next';

export const SHIFT_KINDS = [
    'standard',
    'night',
    'flexible',
    'rotating',
] as const;

export type ShiftKind = (typeof SHIFT_KINDS)[number];

export function shiftKindLabel(
    t: TFunction,
    kind: string | null | undefined,
): string {
    if (!kind) {
        return t('empty_value', { ns: 'common' });
    }

    return t(`kind.${kind}`, {
        ns: 'shifts',
        defaultValue: kind,
    });
}
