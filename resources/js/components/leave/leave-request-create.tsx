import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { LeaveRequestForm } from '@/components/leave/leave-request-form';
import type { LeaveRequestFormValues } from '@/components/leave/leave-request-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import type { LeaveType } from '@/lib/api/modules/leave';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    employeeId: number | null;
    types: LeaveType[];
    values: LeaveRequestFormValues;
    onChange: (values: LeaveRequestFormValues) => void;
    busy: boolean;
    onSubmit: (event: FormEvent) => void;
};

export function LeaveRequestCreate({
    open,
    onOpenChange,
    employeeId,
    types,
    values,
    onChange,
    busy,
    onSubmit,
}: Props) {
    const { t } = useTranslation(['leave', 'common']);
    const isMobile = useIsMobile();

    const canSubmit =
        !busy && !!values.leaveTypeId && !!values.startDate && !!employeeId;

    const body = !employeeId ? (
        <p className="text-sm text-muted-foreground">
            {t('index.no_employee')}
        </p>
    ) : (
        <LeaveRequestForm
            values={values}
            onChange={onChange}
            types={types}
            idPrefix="leave_create"
        />
    );

    const actions = (
        <>
            <Button
                type="button"
                variant="secondary"
                className="min-h-11"
                disabled={busy}
                onClick={() => onOpenChange(false)}
            >
                {t('cancel', { ns: 'common' })}
            </Button>
            <Button
                type="submit"
                form="leave-request-create-form"
                className="min-h-11"
                disabled={!canSubmit}
            >
                {t('index.submit')}
            </Button>
        </>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent
                    side="bottom"
                    className="flex max-h-[90vh] flex-col gap-0 overflow-hidden rounded-t-xl pb-[max(1rem,env(safe-area-inset-bottom))]"
                >
                    <SheetHeader className="border-b border-border text-left">
                        <SheetTitle>{t('index.request_title')}</SheetTitle>
                        <SheetDescription>
                            {t('index.description')}
                        </SheetDescription>
                    </SheetHeader>
                    <form
                        id="leave-request-create-form"
                        onSubmit={onSubmit}
                        className="flex-1 overflow-y-auto p-4"
                    >
                        {body}
                    </form>
                    <SheetFooter className="flex-row gap-2 border-t border-border">
                        {actions}
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('index.request_title')}</DialogTitle>
                    <DialogDescription>
                        {t('index.description')}
                    </DialogDescription>
                </DialogHeader>
                <form id="leave-request-create-form" onSubmit={onSubmit}>
                    {body}
                </form>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button
                            type="button"
                            variant="secondary"
                            disabled={busy}
                        >
                            {t('cancel', { ns: 'common' })}
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        form="leave-request-create-form"
                        disabled={!canSubmit}
                    >
                        {t('index.submit')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
