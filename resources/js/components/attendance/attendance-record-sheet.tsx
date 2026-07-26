import { Link } from '@inertiajs/react';
import { format, isSameDay, isValid, parse } from 'date-fns';
import { useTranslation } from 'react-i18next';
import { AttendanceEvidencePhotoButton } from '@/components/attendance/attendance-evidence-photo-button';
import { AttendanceStatusBadge } from '@/components/attendance/attendance-status-badge';
import { formatDuration } from '@/components/attendance/format-minutes';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type {
    AttendanceEvidence,
    AttendanceRecord,
} from '@/lib/api/modules/attendance';
import { dateFnsLocale } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    record: AttendanceRecord | null;
    pendingCount: number;
    onApprove: (id: number) => void;
};

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

function evidenceFor(
    evidences: AttendanceEvidence[] | undefined,
    punchType: 'check_in' | 'check_out',
): AttendanceEvidence | undefined {
    return evidences?.find((row) => row.punch_type === punchType);
}

export function AttendanceRecordSheet({
    open,
    onOpenChange,
    record,
    pendingCount,
    onApprove,
}: Props) {
    const { t, i18n } = useTranslation(['attendance', 'common']);
    const locale = dateFnsLocale(i18n.language);

    if (!record) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent side="bottom" className="rounded-t-xl" />
            </Sheet>
        );
    }

    const date = parseYmd(record.work_date);
    const isToday = date ? isSameDay(date, new Date()) : false;
    const dateLabel = date
        ? format(date, 'EEEE, d MMMM yyyy', { locale })
        : record.work_date;
    const checkInEvidence = evidenceFor(record.evidences, 'check_in');
    const checkOutEvidence = evidenceFor(record.evidences, 'check_out');

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="max-h-[85vh] gap-0 overflow-y-auto rounded-t-xl pb-[max(1rem,env(safe-area-inset-bottom))]"
            >
                <SheetHeader className="border-b border-border pb-4 text-left">
                    <SheetTitle>{t('index.record_detail_title')}</SheetTitle>
                    <SheetDescription className="capitalize">
                        {dateLabel}
                        {isToday ? ` · ${t('index.today')}` : ''}
                    </SheetDescription>
                </SheetHeader>

                <div className="space-y-4 p-4 pt-4">
                    {record.employee ? (
                        <section className="space-y-1">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('index.col_employee')}
                            </p>
                            <p className="font-medium">
                                {record.employee.full_name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {record.employee.code}
                            </p>
                        </section>
                    ) : null}

                    <section className="space-y-2">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.col_status')}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <AttendanceStatusBadge status={record.status} />
                            {pendingCount > 0 ? (
                                <Badge variant="destructive">
                                    {t('index.review_corrections')} ·{' '}
                                    {pendingCount > 99 ? '99+' : pendingCount}
                                </Badge>
                            ) : null}
                        </div>
                    </section>

                    <section className="space-y-2">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.col_in')} / {t('index.col_out')}
                        </p>
                        <p className="text-sm tabular-nums">
                            {punchTime(record.check_in_at)} →{' '}
                            {punchTime(record.check_out_at)}
                        </p>
                        {checkInEvidence ? (
                            <EvidenceBlock
                                recordId={record.id}
                                label={t('evidence.check_in_label')}
                                evidence={checkInEvidence}
                            />
                        ) : null}
                        {checkOutEvidence ? (
                            <EvidenceBlock
                                recordId={record.id}
                                label={t('evidence.check_out_label')}
                                evidence={checkOutEvidence}
                            />
                        ) : null}
                    </section>

                    <section className="grid grid-cols-3 gap-3">
                        <Metric
                            label={t('index.col_worked')}
                            value={formatDuration(record.worked_minutes, t)}
                        />
                        <Metric
                            label={t('index.col_late')}
                            value={formatDuration(record.late_minutes, t)}
                        />
                        <Metric
                            label={t('index.col_ot')}
                            value={formatDuration(record.overtime_minutes, t)}
                        />
                    </section>

                    <section className="flex flex-col gap-2 pt-1">
                        <PermissionGate permission="can_approve_attendance">
                            {record.status === 'pending' ? (
                                <Button
                                    type="button"
                                    className="min-h-11 w-full"
                                    onClick={() => {
                                        onApprove(record.id);
                                        onOpenChange(false);
                                    }}
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
                                variant="outline"
                                className="relative min-h-11 w-full gap-1.5"
                                asChild
                            >
                                <Link
                                    href={`/attendance/corrections?record_id=${record.id}`}
                                    aria-label={
                                        pendingCount > 0
                                            ? t(
                                                  'index.pending_corrections_aria',
                                                  { count: pendingCount },
                                              )
                                            : t('index.request_correction')
                                    }
                                >
                                    {pendingCount > 0
                                        ? t('index.review_corrections')
                                        : t('index.request_correction')}
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
                    </section>
                </div>
            </SheetContent>
        </Sheet>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border bg-muted/20 px-2 py-2">
            <p className="text-[10px] text-muted-foreground">{label}</p>
            <p className="mt-0.5 text-sm font-semibold tabular-nums">{value}</p>
        </div>
    );
}

function EvidenceBlock({
    recordId,
    label,
    evidence,
}: {
    recordId: number;
    label: string;
    evidence: AttendanceEvidence;
}) {
    return (
        <div className="rounded-md border bg-muted/20 p-2 text-xs text-muted-foreground">
            <p className="font-medium text-foreground">{label}</p>
            <p className="mt-0.5 break-words">{evidence.address}</p>
            {evidence.has_photo ? (
                <AttendanceEvidencePhotoButton
                    recordId={recordId}
                    evidence={evidence}
                    className="mt-1 text-xs text-primary underline-offset-2 hover:underline"
                />
            ) : null}
        </div>
    );
}
