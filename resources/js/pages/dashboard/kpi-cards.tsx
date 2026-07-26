import { CalendarOff, ClipboardCheck, UserPlus, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { CompanyDashboardSummary } from '@/lib/api/modules/dashboard';
import { cn } from '@/lib/utils';

type Props = {
    summary: CompanyDashboardSummary;
};

const CARDS: Array<{
    key: 'active_employees' | 'attendance_today' | 'pending_leave' | 'new_hires';
    label: string;
    icon: typeof Users;
    accent: string;
}> = [
    {
        key: 'active_employees',
        label: 'kpi_active_employees',
        icon: Users,
        accent: 'bg-primary/10 text-primary',
    },
    {
        key: 'attendance_today',
        label: 'kpi_attendance_today',
        icon: ClipboardCheck,
        accent: 'bg-sky-500/10 text-sky-600',
    },
    {
        key: 'pending_leave',
        label: 'kpi_pending_leave',
        icon: CalendarOff,
        accent: 'bg-amber-500/10 text-amber-600',
    },
    {
        key: 'new_hires',
        label: 'kpi_new_hires',
        icon: UserPlus,
        accent: 'bg-primary/10 text-primary',
    },
];

export function KpiCards({ summary }: Props) {
    const { t } = useTranslation('dashboard');

    const values: Record<(typeof CARDS)[number]['key'], string> = {
        active_employees: String(summary.active_employees),
        attendance_today: t('percent', {
            value: Math.round(summary.attendance_today_rate * 100),
        }),
        pending_leave: String(summary.pending_leave_requests),
        new_hires: String(summary.new_hires_month),
    };

    return (
        <div className="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {CARDS.map(({ key, label, icon: Icon, accent }) => (
                <div
                    key={key}
                    className="rounded-xl border border-border bg-card p-5 shadow-sm"
                >
                    <div className="mb-3 flex items-center justify-between">
                        <div
                            className={cn(
                                'flex size-9 items-center justify-center rounded-lg',
                                accent,
                            )}
                        >
                            <Icon className="size-5" />
                        </div>
                        {key === 'pending_leave' &&
                        summary.pending_leave_requests > 0 ? (
                            <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700">
                                {t('pending_actions_title')}
                            </span>
                        ) : null}
                    </div>
                    <p className="text-sm text-muted-foreground">{t(label)}</p>
                    <p className="mt-1 text-3xl font-semibold tracking-tight">
                        {values[key]}
                    </p>
                </div>
            ))}
        </div>
    );
}
