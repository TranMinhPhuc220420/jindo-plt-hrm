import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks/use-mobile';
import type { LeaveRequest } from '@/lib/api/modules/leave';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';
import { cn } from '@/lib/utils';

type Props = {
    requests: LeaveRequest[];
    employeeId: number | null;
    canApproveLeave: boolean;
    canRequestLeave: boolean;
    onApprove: (id: number) => void;
    onReject: (id: number) => void;
    onCancel: (id: number) => void;
    onSelectRequest?: (id: number) => void;
};

function statusVariant(
    status: LeaveRequest['status'],
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status === 'approved') {
        return 'default';
    }

    if (status === 'rejected' || status === 'cancelled') {
        return 'destructive';
    }

    return 'outline';
}

function dateRangeLabel(row: LeaveRequest): string {
    return row.end_date !== row.start_date
        ? `${row.start_date} → ${row.end_date}`
        : row.start_date;
}

export function LeaveRequestsTable({
    requests,
    employeeId,
    canApproveLeave,
    canRequestLeave,
    onApprove,
    onReject,
    onCancel,
    onSelectRequest,
}: Props) {
    const { t } = useTranslation(['leave', 'common']);
    const isMobile = useIsMobile();

    if (isMobile) {
        return (
            <ul className="space-y-2">
                {requests.map((row) => {
                    const employeeLabel =
                        row.employee_name ??
                        row.employee_code ??
                        `#${row.employee_id}`;

                    return (
                        <li key={row.id}>
                            <button
                                type="button"
                                onClick={() => onSelectRequest?.(row.id)}
                                className={cn(
                                    'flex min-h-11 w-full flex-col gap-2 rounded-lg border border-border bg-card px-3 py-3 text-left shadow-sm transition-colors active:bg-muted/50',
                                    row.status === 'pending' &&
                                        'border-primary/30',
                                )}
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="text-sm font-medium">
                                        {leaveTypeLabel(
                                            t,
                                            row.leave_type_code,
                                            row.leave_type_name,
                                        )}
                                    </span>
                                    <Badge variant={statusVariant(row.status)}>
                                        {t(`status.${row.status}`, {
                                            defaultValue: row.status,
                                        })}
                                    </Badge>
                                </div>
                                <p className="truncate text-sm text-muted-foreground">
                                    {employeeLabel}
                                </p>
                                <div className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <p className="text-muted-foreground tabular-nums">
                                        {dateRangeLabel(row)}
                                    </p>
                                    <p className="font-medium tabular-nums">
                                        {row.quantity}
                                    </p>
                                </div>
                            </button>
                        </li>
                    );
                })}
            </ul>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-border">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                    <tr>
                        <th className="px-3 py-2 font-medium">
                            {t('index.col_employee')}
                        </th>
                        <th className="px-3 py-2 font-medium">
                            {t('index.col_type')}
                        </th>
                        <th className="px-3 py-2 font-medium">
                            {t('index.col_dates')}
                        </th>
                        <th className="px-3 py-2 font-medium">
                            {t('index.col_qty')}
                        </th>
                        <th className="px-3 py-2 font-medium">
                            {t('index.col_status')}
                        </th>
                        <th className="px-3 py-2 font-medium" />
                    </tr>
                </thead>
                <tbody>
                    {requests.map((row) => (
                        <tr key={row.id} className="border-t border-border/60">
                            <td className="px-3 py-2">
                                {row.employee_name ??
                                    row.employee_code ??
                                    row.employee_id}
                            </td>
                            <td className="px-3 py-2">
                                {leaveTypeLabel(
                                    t,
                                    row.leave_type_code,
                                    row.leave_type_name,
                                )}
                            </td>
                            <td className="px-3 py-2">{dateRangeLabel(row)}</td>
                            <td className="px-3 py-2">{row.quantity}</td>
                            <td className="px-3 py-2">
                                {t(`status.${row.status}`, {
                                    defaultValue: row.status,
                                })}
                            </td>
                            <td className="px-3 py-2">
                                <div className="flex flex-wrap justify-end gap-2">
                                    {row.status === 'pending' &&
                                        canApproveLeave && (
                                            <>
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        onApprove(row.id)
                                                    }
                                                >
                                                    {t('index.approve')}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        onReject(row.id)
                                                    }
                                                >
                                                    {t('index.reject')}
                                                </Button>
                                            </>
                                        )}
                                    {row.status === 'pending' &&
                                        canRequestLeave &&
                                        row.employee_id === employeeId && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => onCancel(row.id)}
                                            >
                                                {t('index.cancel')}
                                            </Button>
                                        )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
