import { Link } from '@inertiajs/react';
import { format, isSameDay, isValid, parse } from 'date-fns';
import { useTranslation } from 'react-i18next';
import { AttendanceStatusBadge } from '@/components/attendance/attendance-status-badge';
import { AttendanceEvidencePhotoButton } from '@/components/attendance/attendance-evidence-photo-button';
import { formatDuration } from '@/components/attendance/format-minutes';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import type {
    AttendanceEvidence,
    AttendanceRecord,
} from '@/lib/api/modules/attendance';
import { dateFnsLocale } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    records: AttendanceRecord[];
    pendingCorrectionCounts: Record<number, number>;
    onApprove: (id: number) => void;
};

function EvidenceHint({
    recordId,
    evidences,
    punchType,
}: {
    recordId: number;
    evidences?: AttendanceEvidence[];
    punchType: 'check_in' | 'check_out';
}) {
    const evidence = evidences?.find((row) => row.punch_type === punchType);

    if (!evidence) {
        return null;
    }

    return (
        <div className="mt-1 max-w-[12rem] space-y-0.5 text-xs text-muted-foreground">
            <p className="truncate" title={evidence.address}>
                {evidence.address}
            </p>
            <AttendanceEvidencePhotoButton
                recordId={recordId}
                evidence={evidence}
                className="text-xs text-primary underline-offset-2 hover:underline"
            />
        </div>
    );
}

function parseYmd(value: string) {
    const parsed = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(parsed) ? parsed : undefined;
}

function punchTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return format(date, 'HH:mm');
}

export function AttendanceRecordsTable({
    records,
    pendingCorrectionCounts,
    onApprove,
}: Props) {
    const { t, i18n } = useTranslation('attendance');
    const locale = dateFnsLocale(i18n.language);
    const today = new Date();

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-3 py-2">{t('index.col_date')}</th>
                        <th className="px-3 py-2">{t('index.col_employee')}</th>
                        <th className="px-3 py-2">{t('index.col_in')}</th>
                        <th className="px-3 py-2">{t('index.col_out')}</th>
                        <th className="px-3 py-2">{t('index.col_worked')}</th>
                        <th className="px-3 py-2">{t('index.col_late')}</th>
                        <th className="px-3 py-2">{t('index.col_ot')}</th>
                        <th className="px-3 py-2">{t('index.col_status')}</th>
                        <th className="px-3 py-2">{t('index.col_actions')}</th>
                    </tr>
                </thead>
                <tbody>
                    {records.map((row) => {
                        const date = parseYmd(row.work_date);
                        const isToday = date ? isSameDay(date, today) : false;
                        const dateLabel = date
                            ? format(date, 'EEE, d MMM yyyy', { locale })
                            : row.work_date;
                        const pendingCount =
                            pendingCorrectionCounts[row.id] ?? 0;

                        return (
                            <tr
                                key={row.id}
                                className={cn(
                                    'border-t',
                                    isToday && 'bg-primary/5',
                                    pendingCount > 0 && 'bg-destructive/5',
                                )}
                            >
                                <td className="px-3 py-2">
                                    <span className="capitalize">
                                        {dateLabel}
                                    </span>
                                </td>
                                <td className="px-3 py-2">
                                    {row.employee ? (
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {row.employee.full_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {row.employee.code}
                                            </p>
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            #{row.employee_id}
                                        </span>
                                    )}
                                </td>
                                <td className="px-3 py-2">
                                    <div className="tabular-nums">
                                        {punchTime(row.check_in_at)}
                                    </div>
                                    <EvidenceHint
                                        recordId={row.id}
                                        evidences={row.evidences}
                                        punchType="check_in"
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <div className="tabular-nums">
                                        {punchTime(row.check_out_at)}
                                    </div>
                                    <EvidenceHint
                                        recordId={row.id}
                                        evidences={row.evidences}
                                        punchType="check_out"
                                    />
                                </td>
                                <td className="px-3 py-2 tabular-nums">
                                    {formatDuration(row.worked_minutes, t)}
                                </td>
                                <td className="px-3 py-2 tabular-nums">
                                    {formatDuration(row.late_minutes, t)}
                                </td>
                                <td className="px-3 py-2 tabular-nums">
                                    {formatDuration(row.overtime_minutes, t)}
                                </td>
                                <td className="px-3 py-2">
                                    <AttendanceStatusBadge
                                        status={row.status}
                                    />
                                </td>
                                <td className="px-3 py-2">
                                    <div className="flex flex-wrap items-center gap-1">
                                        <PermissionGate permission="can_approve_attendance">
                                            {row.status === 'pending' ? (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        onApprove(row.id)
                                                    }
                                                >
                                                    {t('index.approve')}
                                                </Button>
                                            ) : null}
                                        </PermissionGate>
                                        <PermissionGate
                                            any={[
                                                'can_request_attendance_correction',
                                                'can_approve_attendance',
                                            ]}
                                        >
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="relative gap-1.5"
                                                asChild
                                            >
                                                <Link
                                                    href={`/attendance/corrections?record_id=${row.id}`}
                                                    aria-label={
                                                        pendingCount > 0
                                                            ? t(
                                                                  'index.pending_corrections_aria',
                                                                  {
                                                                      count: pendingCount,
                                                                  },
                                                              )
                                                            : t(
                                                                  'index.request_correction',
                                                              )
                                                    }
                                                >
                                                    {pendingCount > 0
                                                        ? t(
                                                              'index.review_corrections',
                                                          )
                                                        : t(
                                                              'index.request_correction',
                                                          )}
                                                    {pendingCount > 0 ? (
                                                        <span
                                                            className={cn(
                                                                'inline-flex h-4 min-w-4 items-center justify-center rounded-full',
                                                                'bg-destructive px-1 text-[10px] leading-none font-semibold text-white',
                                                            )}
                                                        >
                                                            {pendingCount > 99
                                                                ? '99+'
                                                                : pendingCount}
                                                        </span>
                                                    ) : null}
                                                </Link>
                                            </Button>
                                        </PermissionGate>
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
