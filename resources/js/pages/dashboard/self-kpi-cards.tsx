import { Link } from '@inertiajs/react';
import {
    Bell,
    CalendarDays,
    CalendarOff,
    ClipboardCheck,
    Clock,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { SelfDashboardSummary } from '@/lib/api/modules/dashboard';
import { formatPunchTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    summary: SelfDashboardSummary;
};

export function SelfKpiCards({ summary }: Props) {
    const { t } = useTranslation(['dashboard', 'common']);

    const checkIn = summary.today_attendance?.check_in_at
        ? formatPunchTime(summary.today_attendance.check_in_at)
        : '—';
    const checkOut = summary.today_attendance?.check_out_at
        ? formatPunchTime(summary.today_attendance.check_out_at)
        : '—';

    const cards = [
        {
            key: 'checkin',
            label: t('self_kpi_check_in'),
            value: checkIn,
            icon: ClipboardCheck,
            accent: summary.checked_in_today
                ? 'bg-primary/10 text-primary'
                : 'bg-amber-500/10 text-amber-600',
            badge: summary.checked_in_today
                ? t('self_checked_in')
                : t('self_not_checked_in'),
        },
        {
            key: 'checkout',
            label: t('self_kpi_check_out'),
            value: checkOut,
            icon: Clock,
            accent: 'bg-sky-500/10 text-sky-600',
            badge: null as string | null,
        },
        {
            key: 'leave',
            label: t('self_kpi_pending_leave'),
            value: String(summary.pending_leave_requests),
            icon: CalendarOff,
            accent: 'bg-amber-500/10 text-amber-600',
            badge:
                summary.pending_leave_requests > 0
                    ? t('pending_actions_title')
                    : null,
        },
        {
            key: 'notif',
            label: t('kpi_unread_notifications'),
            value: String(summary.unread_notifications),
            icon: Bell,
            accent: 'bg-primary/10 text-primary',
            badge: null,
        },
    ];

    return (
        <div className="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {cards.map(({ key, label, value, icon: Icon, accent, badge }) => (
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
                        {badge ? (
                            <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                {badge}
                            </span>
                        ) : null}
                    </div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="mt-1 text-3xl font-semibold tracking-tight">
                        {value}
                    </p>
                </div>
            ))}
            <div className="sm:col-span-2 lg:col-span-4">
                <div className="flex flex-wrap gap-2">
                    <Link
                        href="/attendance"
                        className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                    >
                        <ClipboardCheck className="size-4" />
                        {t('self_link_attendance')}
                    </Link>
                    <Link
                        href="/leave"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted/50"
                    >
                        <CalendarOff className="size-4" />
                        {t('self_link_leave')}
                    </Link>
                    <Link
                        href="/my-schedule"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted/50"
                    >
                        <CalendarDays className="size-4" />
                        {t('self_link_schedule')}
                    </Link>
                </div>
            </div>
        </div>
    );
}
