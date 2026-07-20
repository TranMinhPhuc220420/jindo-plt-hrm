import type { FormEvent } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    open: boolean;
    name: string;
    email: string;
    busy: boolean;
    onOpenChange: (open: boolean) => void;
    onNameChange: (value: string) => void;
    onEmailChange: (value: string) => void;
    onSubmit: (event: FormEvent) => void;
};

export default function OrganizationCompanyDialog({
    open,
    name,
    email,
    busy,
    onOpenChange,
    onNameChange,
    onEmailChange,
    onSubmit,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('company_edit_title')}</DialogTitle>
                    <DialogDescription>
                        {t('company_edit_description')}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={onSubmit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="company-name">
                            {t('company_name')}
                        </Label>
                        <Input
                            id="company-name"
                            value={name}
                            onChange={(event) =>
                                onNameChange(event.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="company-email">
                            {t('email', { ns: 'common' })}
                        </Label>
                        <Input
                            id="company-email"
                            type="email"
                            value={email}
                            onChange={(event) =>
                                onEmailChange(event.target.value)
                            }
                        />
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                {t('cancel', { ns: 'common' })}
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={busy}>
                            {t('save_company')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
