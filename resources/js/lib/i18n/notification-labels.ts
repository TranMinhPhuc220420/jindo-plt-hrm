import type { TFunction } from 'i18next';

type NotificationLike = {
    type: string;
    title: string;
    body?: string | null;
};

/**
 * Localize inbox title by stable notification `type` (e.g. `leave.approved`).
 * Falls back to the stored API title for unknown / custom types.
 */
export function notificationTitle(
    t: TFunction,
    item: NotificationLike,
): string {
    return t(`messages.${item.type}.title`, {
        ns: 'notifications',
        defaultValue: item.title,
    });
}

export function notificationBody(
    t: TFunction,
    item: NotificationLike,
): string | null {
    if (!item.body && !item.type) {
        return null;
    }

    const translated = t(`messages.${item.type}.body`, {
        ns: 'notifications',
        defaultValue: item.body ?? '',
    });

    return translated || null;
}

/** Domain prefix label for the type code shown as metadata. */
export function notificationTypeLabel(
    t: TFunction,
    type: string,
): string {
    const domain = type.includes('.') ? type.split('.')[0]! : type;

    return t(`types.${domain}`, {
        ns: 'notifications',
        defaultValue: type,
    });
}
