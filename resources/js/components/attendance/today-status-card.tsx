import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AttendanceEvidencePhotoButton } from '@/components/attendance/attendance-evidence-photo-button';
import { AttendanceStatusBadge } from '@/components/attendance/attendance-status-badge';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks/use-mobile';
import type {
    AttendanceEvidence,
    AttendanceRecord,
} from '@/lib/api/modules/attendance';
import { dateFnsLocale, displayTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    employeeId: number | null;
    today: AttendanceRecord | null;
    sessions?: AttendanceRecord[];
    expectedSessionCount?: number | null;
    busy: boolean;
    pendingCheckIn?: boolean;
    pendingCheckOut?: boolean;
    hasShiftToday?: boolean | null;
    onCheckIn: () => void;
    onCheckOut: () => void;
};

function punchTimeLabel(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    try {
        const date = new Date(value);

        if (!Number.isNaN(date.getTime())) {
            return format(date, 'HH:mm');
        }
    } catch {
        // fall through
    }

    return displayTime(value) || value;
}

function evidenceFor(
    evidences: AttendanceEvidence[] | undefined,
    punchType: 'check_in' | 'check_out',
): AttendanceEvidence | undefined {
    return evidences?.find((row) => row.punch_type === punchType);
}

export function TodayStatusCard({
    employeeId,
    today,
    sessions,
    expectedSessionCount = null,
    busy,
    pendingCheckIn = false,
    pendingCheckOut = false,
    hasShiftToday = null,
    onCheckIn,
    onCheckOut,
}: Props) {
    const { t, i18n } = useTranslation(['attendance', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const isMobile = useIsMobile();
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const id = window.setInterval(() => setNow(new Date()), 30_000);

        return () => window.clearInterval(id);
    }, []);

    const sessionList = sessions ?? (today ? [today] : []);
    const openSession = sessionList.find(
        (row) => row.check_in_at && !row.check_out_at,
    );
    const expected = expectedSessionCount ?? Math.max(sessionList.length, 1);
    const completedCount = sessionList.filter((row) => row.check_out_at).length;
    const canCheckIn =
        Boolean(employeeId) &&
        hasShiftToday !== false &&
        !openSession &&
        completedCount < expected;
    const canCheckOut = Boolean(openSession);

    let stateKey:
        'state_no_employee' | 'state_not_in' | 'state_working' | 'state_done' =
        'state_not_in';

    if (!employeeId) {
        stateKey = 'state_no_employee';
    } else if (openSession) {
        stateKey = 'state_working';
    } else if (expected > 0 && completedCount >= expected) {
        stateKey = 'state_done';
    }

    const active = openSession ?? today;
    const checkInEvidence = evidenceFor(active?.evidences, 'check_in');
    const checkOutEvidence = evidenceFor(active?.evidences, 'check_out');

    return (
        <div className="mb-6 rounded-xl border bg-muted/20 p-4 sm:p-6">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-2">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        {t('index.today')}
                    </p>
                    <p className="text-2xl font-semibold capitalize sm:text-3xl">
                        {format(now, 'EEEE, d MMMM yyyy', { locale })}
                    </p>
                    <p className="text-muted-foreground tabular-nums">
                        {format(now, 'HH:mm')}
                    </p>
                    <p className="text-sm font-medium">
                        {t(`index.${stateKey}`)}
                    </p>
                    {employeeId && hasShiftToday === false ? (
                        <p className="text-sm text-destructive" role="status">
                            {t('index.no_shift_banner')}
                        </p>
                    ) : null}

                    {sessionList.length > 0 ? (
                        <div className="space-y-2 pt-1 text-sm">
                            {sessionList.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex flex-wrap items-center gap-2"
                                >
                                    {row.shift?.name ? (
                                        <span className="font-medium">
                                            {row.shift.name}
                                        </span>
                                    ) : null}
                                    <span className="text-muted-foreground tabular-nums">
                                        {punchTimeLabel(row.check_in_at)}
                                        {' → '}
                                        {row.check_out_at
                                            ? punchTimeLabel(row.check_out_at)
                                            : t('empty_value', {
                                                  ns: 'common',
                                              })}
                                    </span>
                                    <AttendanceStatusBadge
                                        status={row.status}
                                    />
                                </div>
                            ))}
                        </div>
                    ) : null}

                    {active && (checkInEvidence || checkOutEvidence) ? (
                        <div className="space-y-2 pt-2 text-sm">
                            {checkInEvidence ? (
                                <EvidenceLine
                                    recordId={active.id}
                                    label={t('evidence.check_in_label')}
                                    evidence={checkInEvidence}
                                />
                            ) : null}
                            {checkOutEvidence ? (
                                <EvidenceLine
                                    recordId={active.id}
                                    label={t('evidence.check_out_label')}
                                    evidence={checkOutEvidence}
                                />
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <div
                    className={cn(
                        'flex items-center gap-2',
                        isMobile ? 'w-full flex-col' : 'flex-wrap',
                    )}
                >
                    <PermissionGate permission="can_check_in_out">
                        {!employeeId ? (
                            <p className="text-sm text-muted-foreground">
                                {t('index.no_employee')}
                            </p>
                        ) : (
                            <>
                                <Button
                                    type="button"
                                    size="lg"
                                    className={cn(
                                        isMobile && 'min-h-11 w-full',
                                    )}
                                    disabled={
                                        busy || !canCheckIn || pendingCheckIn
                                    }
                                    onClick={onCheckIn}
                                >
                                    {t('index.check_in')}
                                </Button>
                                <Button
                                    type="button"
                                    size="lg"
                                    variant="secondary"
                                    className={cn(
                                        isMobile && 'min-h-11 w-full',
                                    )}
                                    disabled={
                                        busy || !canCheckOut || pendingCheckOut
                                    }
                                    onClick={onCheckOut}
                                >
                                    {t('index.check_out')}
                                </Button>
                            </>
                        )}
                    </PermissionGate>
                    <Button
                        variant={isMobile ? 'ghost' : 'outline'}
                        size="lg"
                        className={cn(isMobile && 'min-h-11 w-full')}
                        asChild
                    >
                        <Link href="/attendance/corrections">
                            {t('index.corrections_link')}
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}

function EvidenceLine({
    recordId,
    label,
    evidence,
}: {
    recordId: number;
    label: string;
    evidence: AttendanceEvidence;
}) {
    return (
        <div className="max-w-xl text-muted-foreground">
            <span className="font-medium text-foreground">{label}: </span>
            <span>{evidence.address}</span>
            {evidence.has_photo ? (
                <>
                    {' · '}
                    <AttendanceEvidencePhotoButton
                        recordId={recordId}
                        evidence={evidence}
                    />
                </>
            ) : null}
        </div>
    );
}
