import { useTranslation } from 'react-i18next';
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
import type { DeleteDialogState } from './org-types';

type Props = {
    state: DeleteDialogState | null;
    busy: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
};

export default function OrganizationDeleteDialog({
    state,
    busy,
    onOpenChange,
    onConfirm,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);

    if (!state) {
        return null;
    }

    return (
        <Dialog open={state.open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('delete_title')}</DialogTitle>
                    <DialogDescription>
                        {t(`confirm_delete_${state.kind}`, {
                            name: state.name,
                        })}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            {t('cancel', { ns: 'common' })}
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={busy}
                        onClick={onConfirm}
                    >
                        {t('delete', { ns: 'common' })}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
