import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { LeaveBalanceRow } from '@/lib/api/modules/dashboard';

type Props = {
    balances: LeaveBalanceRow[];
};

export function LeaveBalancesPanel({ balances }: Props) {
    const { t } = useTranslation('dashboard');

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-2">
                <h2 className="text-lg font-semibold">
                    {t('self_leave_balances_title')}
                </h2>
                <Link
                    href="/leave"
                    className="text-sm font-medium text-primary hover:underline"
                >
                    {t('view_leave')}
                </Link>
            </div>
            {balances.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('self_leave_balances_empty')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {balances.map((row) => (
                        <li
                            key={row.leave_type_id}
                            className="flex items-center justify-between rounded-lg border border-border px-3 py-2.5"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">
                                    {row.leave_type_name || row.leave_type_code}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {t('self_leave_used', {
                                        used: row.used,
                                        entitled: row.entitled,
                                    })}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-lg font-semibold tabular-nums">
                                    {row.remaining}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {t('self_leave_remaining')}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
