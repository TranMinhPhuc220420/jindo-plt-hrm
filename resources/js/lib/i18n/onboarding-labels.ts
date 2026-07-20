import type { TFunction } from 'i18next';

/** Known seeded template display names → i18n key under `onboarding:templates.*`. */
const TEMPLATE_NAME_KEYS: Record<string, string> = {
    'Default onboarding': 'default_onboarding',
};

/**
 * Localize a checklist task title by stable `key`, falling back to API title.
 */
export function onboardingTaskTitle(
    t: TFunction,
    key: string | null | undefined,
    fallbackTitle?: string | null,
): string {
    if (key) {
        return t(`tasks.${key}`, {
            ns: 'onboarding',
            defaultValue: fallbackTitle || key,
        });
    }

    return fallbackTitle || t('empty_value', { ns: 'common' });
}

/**
 * Localize known system template names; custom names stay as stored.
 */
export function onboardingTemplateLabel(
    t: TFunction,
    name: string | null | undefined,
): string {
    if (!name) {
        return t('empty_value', { ns: 'common' });
    }

    const key = TEMPLATE_NAME_KEYS[name];

    if (key) {
        return t(`templates.${key}`, {
            ns: 'onboarding',
            defaultValue: name,
        });
    }

    return name;
}

export function onboardingAssigneeLabel(
    t: TFunction,
    assigneeType: string | null | undefined,
): string | null {
    if (!assigneeType) {
        return null;
    }

    return t(`assignee.${assigneeType}`, {
        ns: 'onboarding',
        defaultValue: assigneeType,
    });
}

export function onboardingProgressLabel(
    t: TFunction,
    progress: { done: number; total: number } | null | undefined,
): string {
    if (!progress) {
        return t('empty_value', { ns: 'common' });
    }

    return t('progress_of', {
        ns: 'onboarding',
        done: progress.done,
        total: progress.total,
    });
}
