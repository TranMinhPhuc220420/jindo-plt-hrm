import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { LeaveRequest } from '@/lib/api/modules/leave';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    request: LeaveRequest | null;
    canApprove: boolean;
    canCancel: boolean;
    onApprove: (id: number) => void;
    onReject: (id: number) => void;
    onCancel: (id: number) => void;
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

export function LeaveRequestSheet({
    open,
    onOpenChange,
    request,
    canApprove,
    canCancel,
    onApprove,
    onReject,
    onCancel,
}: Props) {
    const { t } = useTranslation(['leave', 'common']);

    if (!request) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent side="bottom" className="rounded-t-xl" />
            </Sheet>
        );
    }

    const dateLabel =
        request.end_date !== request.start_date
            ? `${request.start_date} → ${request.end_date}`
            : request.start_date;

    const employeeLabel =
        request.employee_name ??
        request.employee_code ??
        `#${request.employee_id}`;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="max-h-[85vh] gap-0 overflow-y-auto rounded-t-xl pb-[max(1rem,env(safe-area-inset-bottom))]"
            >
                <SheetHeader className="border-b border-border pb-4 text-left">
                    <SheetTitle>{t('index.request_detail_title')}</SheetTitle>
                    <SheetDescription>
                        {leaveTypeLabel(
                            t,
                            request.leave_type_code,
                            request.leave_type_name,
                        )}
                    </SheetDescription>
                </SheetHeader>

                <div className="space-y-4 p-4 pt-4">
                    <section className="space-y-1">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.col_employee')}
                        </p>
                        <p className="text-sm font-medium">{employeeLabel}</p>
                    </section>

                    <section className="space-y-2">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.col_status')}
                        </p>
                        <Badge variant={statusVariant(request.status)}>
                            {t(`status.${request.status}`, {
                                defaultValue: request.status,
                            })}
                        </Badge>
                    </section>

                    <section className="space-y-1">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.col_dates')}
                        </p>
                        <p className="text-sm tabular-nums">{dateLabel}</p>
                        <p className="text-sm text-muted-foreground">
                            {t('index.col_qty')}: {request.quantity}
                        </p>
                    </section>

                    <section className="space-y-1">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('index.reason')}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {request.reason ||
                                t('empty_value', { ns: 'common' })}
                        </p>
                    </section>

                    {request.status === 'pending' &&
                    (canApprove || canCancel) ? (
                        <section className="flex flex-col gap-2 pt-1">
                            {canApprove ? (
                                <>
                                    <Button
                                        type="button"
                                        className="min-h-11 w-full"
                                        onClick={() => {
                                            onApprove(request.id);
                                            onOpenChange(false);
                                        }}
                                    >
                                        {t('index.approve')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="min-h-11 w-full"
                                        onClick={() => {
                                            onReject(request.id);
                                            onOpenChange(false);
                                        }}
                                    >
                                        {t('index.reject')}
                                    </Button>
                                </>
                            ) : null}
                            {canCancel ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="min-h-11 w-full"
                                    onClick={() => {
                                        onCancel(request.id);
                                        onOpenChange(false);
                                    }}
                                >
                                    {t('index.cancel')}
                                </Button>
                            ) : null}
                        </section>
                    ) : null}
                </div>
            </SheetContent>
        </Sheet>
    );
}
