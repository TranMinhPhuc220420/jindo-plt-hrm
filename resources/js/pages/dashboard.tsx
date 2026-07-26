import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ErrorState, LoadingState } from '@/components/shared/async-state';
import { ApiError } from '@/lib/api/errors';
import * as dashboardApi from '@/lib/api/modules/dashboard';
import type { DashboardSummary } from '@/lib/api/modules/dashboard';
import { useAuth } from '@/lib/auth/auth-context';
import i18n from '@/lib/i18n';
import { dashboard } from '@/routes';
import { AttendanceChart } from './dashboard/attendance-chart';
import { HeadcountChart } from './dashboard/headcount-chart';
import { KpiCards } from './dashboard/kpi-cards';
import { LeaveBalancesPanel } from './dashboard/leave-balances-panel';
import { MyAttendanceChart } from './dashboard/my-attendance-chart';
import { PendingActions } from './dashboard/pending-actions';
import { RecentActivity } from './dashboard/recent-activity';
import { RecentHires } from './dashboard/recent-hires';
import { SelfKpiCards } from './dashboard/self-kpi-cards';
import { UpcomingPanel } from './dashboard/upcoming-panel';

export default function Dashboard() {
    const { t } = useTranslation(['dashboard', 'nav', 'common']);
    const { user } = useAuth();
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

    const isSelf = summary?.scope === 'self';
    const subtitle = isSelf ? t('self_subtitle') : t('subtitle');

    return (
        <>
            <Head title={t('title')} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 md:gap-6 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                        {user?.name
                            ? t('welcome', { name: user.name })
                            : t('welcome_fallback')}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {subtitle}
                    </p>
                    {summary?.scope === 'self' && summary.employee ? (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {summary.employee.code}
                            {summary.employee.department_name
                                ? ` · ${summary.employee.department_name}`
                                : ''}
                            {' · '}
                            {t(`status_${summary.employee.status}`, {
                                ns: 'common',
                                defaultValue: summary.employee.status,
                            })}
                        </p>
                    ) : null}
                </div>

                {loading ? (
                    <LoadingState label={t('loading')} />
                ) : error ? (
                    <ErrorState message={error} />
                ) : summary?.scope === 'company' ? (
                    <>
                        <KpiCards summary={summary} />

                        <div className="flex flex-col gap-4 lg:grid lg:grid-cols-3 lg:items-start">
                            <div className="order-2 space-y-4 lg:order-1 lg:col-span-2">
                                <AttendanceChart
                                    series={summary.attendance_last_7_days}
                                />
                                <div className="grid gap-4 md:grid-cols-2">
                                    <HeadcountChart
                                        byStatus={summary.employees_by_status}
                                        byDepartment={
                                            summary.employees_by_department
                                        }
                                    />
                                    <RecentHires hires={summary.recent_hires} />
                                </div>
                            </div>
                            <div className="order-1 space-y-4 lg:order-2">
                                <PendingActions
                                    actions={summary.pending_actions}
                                />
                                <UpcomingPanel items={summary.upcoming} />
                                <RecentActivity
                                    items={summary.recent_activity}
                                />
                            </div>
                        </div>
                    </>
                ) : summary?.scope === 'self' ? (
                    <>
                        {!summary.employee ? (
                            <ErrorState message={t('self_no_employee')} />
                        ) : (
                            <SelfKpiCards summary={summary} />
                        )}

                        <div className="flex flex-col gap-4 lg:grid lg:grid-cols-3 lg:items-start">
                            <div className="order-2 space-y-4 lg:order-1 lg:col-span-2">
                                <MyAttendanceChart
                                    series={summary.my_attendance_last_7_days}
                                />
                                <LeaveBalancesPanel
                                    balances={summary.leave_balances}
                                />
                            </div>
                            <div className="order-1 space-y-4 lg:order-2">
                                <PendingActions
                                    actions={summary.pending_actions}
                                />
                                <UpcomingPanel items={summary.upcoming} />
                                <RecentActivity
                                    items={summary.recent_activity}
                                />
                            </div>
                        </div>
                    </>
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
