import { format, parseISO } from 'date-fns';
import { useTranslation } from 'react-i18next';
import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import type { AttendanceDayPoint } from '@/lib/api/modules/dashboard';
import { dateFnsLocale } from '@/lib/datetime';

type Props = {
    series: AttendanceDayPoint[];
};

export function AttendanceChart({ series }: Props) {
    const { t, i18n } = useTranslation('dashboard');
    const locale = dateFnsLocale(i18n.language);

    const data = series.map((point) => {
        let weekday = point.label;

        try {
            weekday = format(parseISO(point.date), 'EEE', { locale });
        } catch {
            // keep API label
        }

        return {
            ...point,
            weekday,
            percent: Math.round(point.rate * 100),
        };
    });

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4">
                <h2 className="text-lg font-semibold">
                    {t('attendance_chart_title')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t('attendance_chart_subtitle')}
                </p>
            </div>
            <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={data} margin={{ top: 8, right: 8, left: -12, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                        <XAxis
                            dataKey="weekday"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 12 }}
                        />
                        <YAxis
                            domain={[0, 100]}
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 12 }}
                            tickFormatter={(v: number) => `${v}%`}
                        />
                        <Tooltip
                            formatter={(value) => [
                                t('percent', {
                                    value:
                                        typeof value === 'number'
                                            ? value
                                            : Number(value) || 0,
                                }),
                                t('kpi_attendance_today'),
                            ]}
                            labelFormatter={(_, payload) => {
                                const row = payload?.[0]?.payload as
                                    | {
                                          date?: string;
                                          present?: number;
                                          expected?: number;
                                      }
                                    | undefined;

                                if (!row?.date) {
                                    return '';
                                }

                                return `${row.date} · ${row.present}/${row.expected}`;
                            }}
                        />
                        <Bar
                            dataKey="percent"
                            fill="#059669"
                            radius={[6, 6, 0, 0]}
                            maxBarSize={40}
                        />
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </section>
    );
}
