import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { useEffect, useState } from 'react';
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
import { dateFnsLocale, displayTime } from '@/lib/datetime';

type Props = {
    employeeId: number | null;
    today: AttendanceRecord | null;
    busy: boolean;
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
    busy,
    onCheckIn,
    onCheckOut,
}: Props) {
    const { t, i18n } = useTranslation(['attendance', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const id = window.setInterval(() => setNow(new Date()), 30_000);

        return () => window.clearInterval(id);
    }, []);

    let stateKey:
        'state_no_employee' | 'state_not_in' | 'state_working' | 'state_done' =
        'state_not_in';

    if (!employeeId) {
        stateKey = 'state_no_employee';
    } else if (today?.check_in_at && today.check_out_at) {
        stateKey = 'state_done';
    } else if (today?.check_in_at) {
        stateKey = 'state_working';
    }

    const checkInEvidence = evidenceFor(today?.evidences, 'check_in');
    const checkOutEvidence = evidenceFor(today?.evidences, 'check_out');

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

                    {today ? (
                        <div className="flex flex-wrap items-center gap-2 pt-1 text-sm">
                            <span className="text-muted-foreground tabular-nums">
                                {punchTimeLabel(today.check_in_at)}
                                {' → '}
                                {today.check_out_at
                                    ? punchTimeLabel(today.check_out_at)
                                    : t('empty_value', { ns: 'common' })}
                            </span>
                            <AttendanceStatusBadge status={today.status} />
                            {today.late_minutes > 0 ? (
                                <span className="text-muted-foreground">
                                    {t('index.col_late')}:{' '}
                                    {formatDuration(today.late_minutes, t)}
                                </span>
                            ) : null}
                            {today.overtime_minutes > 0 ? (
                                <span className="text-muted-foreground">
                                    {t('index.col_ot')}:{' '}
                                    {formatDuration(today.overtime_minutes, t)}
                                </span>
                            ) : null}
                        </div>
                    ) : null}

                    {today && (checkInEvidence || checkOutEvidence) ? (
                        <div className="space-y-2 pt-2 text-sm">
                            {checkInEvidence ? (
                                <EvidenceLine
                                    recordId={today.id}
                                    label={t('evidence.check_in_label')}
                                    evidence={checkInEvidence}
                                />
                            ) : null}
                            {checkOutEvidence ? (
                                <EvidenceLine
                                    recordId={today.id}
                                    label={t('evidence.check_out_label')}
                                    evidence={checkOutEvidence}
                                />
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <div className="flex flex-wrap items-center gap-2">
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
                                    disabled={busy || !!today?.check_in_at}
                                    onClick={onCheckIn}
                                >
                                    {t('index.check_in')}
                                </Button>
                                <Button
                                    type="button"
                                    size="lg"
                                    variant="secondary"
                                    disabled={
                                        busy ||
                                        !today?.check_in_at ||
                                        !!today?.check_out_at
                                    }
                                    onClick={onCheckOut}
                                >
                                    {t('index.check_out')}
                                </Button>
                            </>
                        )}
                    </PermissionGate>
                    <Button variant="outline" size="lg" asChild>
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
