import { Link } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { useTranslation } from 'react-i18next';
import type { RecentHire } from '@/lib/api/modules/dashboard';
import { dateFnsLocale } from '@/lib/datetime';

type Props = {
    hires: RecentHire[];
};

export function RecentHires({ hires }: Props) {
    const { t, i18n } = useTranslation(['dashboard', 'common']);
    const locale = dateFnsLocale(i18n.language);

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-2">
                <h2 className="text-lg font-semibold">
                    {t('recent_hires_title')}
                </h2>
                <Link
                    href="/employees"
                    className="text-sm font-medium text-primary hover:underline"
                >
                    {t('view_employees')}
                </Link>
            </div>
            {hires.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('recent_hires_empty')}
                </p>
            ) : (
                <ul className="divide-y divide-border">
                    {hires.map((hire) => {
                        let hiredLabel = hire.hired_at ?? '—';

                        if (hire.hired_at) {
                            try {
                                hiredLabel = format(
                                    parseISO(hire.hired_at),
                                    'PP',
                                    {
                                        locale,
                                    },
                                );
                            } catch {
                                // keep ISO
                            }
                        }

                        const statusKey = `status_${hire.status}`;

                        return (
                            <li
                                key={hire.id}
                                className="py-2.5 first:pt-0 last:pb-0"
                            >
                                <Link
                                    href={`/employees/${hire.id}`}
                                    className="block rounded-md transition-colors hover:bg-muted/40"
                                >
                                    <div className="flex items-start justify-between gap-2 px-1 py-0.5">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {hire.full_name}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {hire.code}
                                                {hire.department_name
                                                    ? ` · ${hire.department_name}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="shrink-0 text-right">
                                            <p className="text-xs text-muted-foreground">
                                                {hiredLabel}
                                            </p>
                                            <p className="text-xs font-medium">
                                                {t(statusKey, {
                                                    ns: 'common',
                                                    defaultValue: hire.status,
                                                })}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
