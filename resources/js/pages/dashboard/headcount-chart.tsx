import { useTranslation } from 'react-i18next';
import {
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';
import type {
    DepartmentCount,
    StatusCount,
} from '@/lib/api/modules/dashboard';

type Props = {
    byStatus: StatusCount[];
    byDepartment: DepartmentCount[];
};

const COLORS = ['#059669', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'];

function statusLabel(
    status: string,
    t: (key: string, opts?: Record<string, unknown>) => string,
): string {
    const key = `status_${status}`;
    const translated = t(key, { ns: 'common', defaultValue: '' });

    return translated || status;
}

function departmentLabel(
    name: string,
    t: (key: string) => string,
): string {
    if (name === 'Unassigned') {
        return t('unassigned');
    }

    if (name === 'Other') {
        return t('other');
    }

    return name;
}

export function HeadcountChart({ byStatus, byDepartment }: Props) {
    const { t } = useTranslation(['dashboard', 'common']);

    const data = byStatus.map((row) => ({
        name: statusLabel(row.status, t),
        value: row.count,
        status: row.status,
    }));

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4">
                <h2 className="text-lg font-semibold">
                    {t('headcount_chart_title')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t('headcount_chart_subtitle')}
                </p>
            </div>
            <div className="h-56 w-full">
                {data.length === 0 ? (
                    <p className="flex h-full items-center justify-center text-sm text-muted-foreground">
                        {t('recent_hires_empty')}
                    </p>
                ) : (
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                dataKey="value"
                                nameKey="name"
                                innerRadius={48}
                                outerRadius={78}
                                paddingAngle={2}
                            >
                                {data.map((entry, index) => (
                                    <Cell
                                        key={entry.status}
                                        fill={COLORS[index % COLORS.length]}
                                    />
                                ))}
                            </Pie>
                            <Tooltip />
                            <Legend
                                verticalAlign="bottom"
                                height={32}
                                wrapperStyle={{ fontSize: 12 }}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                )}
            </div>
            {byDepartment.length > 0 ? (
                <div className="mt-3 border-t border-border pt-3">
                    <p className="mb-2 text-xs font-medium text-muted-foreground">
                        {t('department_chart_title')}
                    </p>
                    <ul className="space-y-1.5">
                        {byDepartment.map((row) => (
                            <li
                                key={`${row.department_id ?? 'x'}-${row.name}`}
                                className="flex items-center justify-between text-sm"
                            >
                                <span className="truncate text-muted-foreground">
                                    {departmentLabel(row.name, t)}
                                </span>
                                <span className="font-medium tabular-nums">
                                    {row.count}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}
        </section>
    );
}
