import { useTranslation } from 'react-i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import type { AttendanceSummary } from '@/lib/api/modules/attendance';

type Props = {
    summary: AttendanceSummary | null;
    loading: boolean;
    error: string | null;
};

export function MonthSummaryStrip({ summary, loading, error }: Props) {
    const { t } = useTranslation('attendance');

    if (loading) {
        return (
            <p className="mb-4 text-sm text-muted-foreground">
                {t('index.summary_loading')}
            </p>
        );
    }

    if (error) {
        return <p className="mb-4 text-sm text-destructive">{error}</p>;
    }

    if (!summary) {
        return null;
    }

    const items = [
        {
            label: t('index.summary_present'),
            value: String(summary.days_present),
        },
        {
            label: t('index.summary_worked'),
            value: formatDuration(summary.worked_minutes, t),
        },
        {
            label: t('index.summary_late'),
            value: formatDuration(summary.late_minutes, t),
        },
        {
            label: t('index.summary_ot'),
            value: formatDuration(summary.overtime_minutes, t),
        },
    ];

    return (
        <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            {items.map((item) => (
                <div
                    key={item.label}
                    className="rounded-lg border bg-card px-3 py-3"
                >
                    <p className="text-xs text-muted-foreground">
                        {item.label}
                    </p>
                    <p className="mt-1 text-lg font-semibold tabular-nums">
                        {item.value}
                    </p>
                </div>
            ))}
        </div>
    );
}
