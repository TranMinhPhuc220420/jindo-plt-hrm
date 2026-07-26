import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { PendingAction } from '@/lib/api/modules/dashboard';

type Props = {
    actions: PendingAction[];
};

const LABEL_KEYS: Record<string, string> = {
    pending_leave: 'pending_leave',
    pending_corrections: 'pending_corrections',
    open_payroll: 'open_payroll',
    my_pending_leave: 'my_pending_leave',
    my_pending_corrections: 'my_pending_corrections',
    unread_notifications: 'kpi_unread_notifications',
};

export function PendingActions({ actions }: Props) {
    const { t } = useTranslation('dashboard');

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <h2 className="mb-4 text-lg font-semibold">
                {t('pending_actions_title')}
            </h2>
            {actions.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('pending_actions_empty')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {actions.map((action) => (
                        <li key={action.key}>
                            <Link
                                href={action.href}
                                className="flex items-center justify-between rounded-lg border border-border px-3 py-2.5 text-sm transition-colors hover:bg-muted/50"
                            >
                                <span>
                                    {t(LABEL_KEYS[action.key] ?? action.key)}
                                </span>
                                <span className="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                    {action.count}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
