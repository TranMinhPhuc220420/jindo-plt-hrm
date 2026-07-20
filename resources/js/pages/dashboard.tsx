import { Head } from '@inertiajs/react';
import { CalendarOff, Users, Wallet, Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ErrorState, LoadingState } from '@/components/shared/async-state';
import { ApiError } from '@/lib/api/errors';
import * as dashboardApi from '@/lib/api/modules/dashboard';
import type { DashboardSummary } from '@/lib/api/modules/dashboard';
import i18n from '@/lib/i18n';
import { dashboard } from '@/routes';

const KPI_META: Array<{
    key: keyof DashboardSummary;
    label: string;
    icon: typeof Users;
}> = [
    { key: 'active_employees', label: 'kpi_active_employees', icon: Users },
    {
        key: 'pending_leave_requests',
        label: 'kpi_pending_leave',
        icon: CalendarOff,
    },
    { key: 'open_payroll_runs', label: 'kpi_open_payroll', icon: Wallet },
    {
        key: 'unread_notifications',
        label: 'kpi_unread_notifications',
        icon: Bell,
    },
];

export default function Dashboard() {
    const { t } = useTranslation(['dashboard', 'nav']);
    const [summary, setSummary] = useState<DashboardSummary | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let active = true;

        (async () => {
            setLoading(true);
            setError(null);

            try {
                const data = await dashboardApi.getSummary();

                if (active) {
                    setSummary(data);
                }
            } catch (err) {
                if (active) {
                    setError(
                        err instanceof ApiError ? err.message : t('error_load'),
                    );
                }
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        })();

        return () => {
            active = false;
        };
    }, [t]);

    return (
        <>
            <Head title={t('title')} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">{t('title')}</h1>
                    <p className="text-sm text-muted-foreground">
                        {t('description')}
                    </p>
                </div>

                {loading ? (
                    <LoadingState label={t('loading')} />
                ) : error ? (
                    <ErrorState message={error} />
                ) : summary ? (
                    <div className="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {KPI_META.map(({ key, label, icon: Icon }) => (
                            <div
                                key={key}
                                className="rounded-xl border border-border bg-card p-5 shadow-sm"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        {t(label)}
                                    </span>
                                    <Icon className="size-5 text-muted-foreground" />
                                </div>
                                <p className="mt-3 text-3xl font-semibold">
                                    {summary[key]}
                                </p>
                            </div>
                        ))}
                    </div>
                ) : null}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: i18n.t('nav:dashboard'),
            href: dashboard(),
        },
    ],
};
