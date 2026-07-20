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
import type { OrganizationTree } from '@/lib/api/modules/organization';
import type { EntityDialogState } from './org-types';

type Props = {
    state: EntityDialogState;
    tree: OrganizationTree;
    busy: boolean;
    onOpenChange: (open: boolean) => void;
    onChange: (patch: Partial<EntityDialogState>) => void;
    onSubmit: (event: FormEvent) => void;
};

export default function OrganizationEntityDialog({
    state,
    tree,
    busy,
    onOpenChange,
    onChange,
    onSubmit,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);

    const titleKey =
        state.mode === 'create'
            ? `create_${state.kind}_title`
            : `edit_${state.kind}_title`;
    const descriptionKey =
        state.mode === 'create'
            ? `create_${state.kind}_description`
            : `edit_${state.kind}_description`;
    const submitKey =
        state.mode === 'create' ? `create_${state.kind}` : 'save_changes';

    const departmentOptions = tree.branches.flatMap((branch) =>
        branch.departments.map((department) => ({
            id: department.id,
            label: `${branch.name} / ${department.name}`,
        })),
    );

    return (
        <Dialog open={state.open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t(titleKey)}</DialogTitle>
                    <DialogDescription>{t(descriptionKey)}</DialogDescription>
                </DialogHeader>
                <form onSubmit={onSubmit} className="grid gap-4">
                    {state.kind === 'department' && state.mode === 'create' && (
                        <div className="grid gap-2">
                            <Label htmlFor="entity-branch">
                                {t('branch_label')}
                            </Label>
                            <select
                                id="entity-branch"
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={state.branchId}
                                onChange={(event) =>
                                    onChange({
                                        branchId: event.target.value
                                            ? Number(event.target.value)
                                            : '',
                                    })
                                }
                                required
                            >
                                <option value="">{t('select_branch')}</option>
                                {tree.branches.map((branch) => (
                                    <option key={branch.id} value={branch.id}>
                                        {branch.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {state.kind === 'team' && state.mode === 'create' && (
                        <div className="grid gap-2">
                            <Label htmlFor="entity-department">
                                {t('department_label')}
                            </Label>
                            <select
                                id="entity-department"
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={state.departmentId}
                                onChange={(event) =>
                                    onChange({
                                        departmentId: event.target.value
                                            ? Number(event.target.value)
                                            : '',
                                    })
                                }
                                required
                            >
                                <option value="">
                                    {t('select_department')}
                                </option>
                                {departmentOptions.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="entity-name">
                                {t('name', { ns: 'common' })}
                            </Label>
                            <Input
                                id="entity-name"
                                value={state.name}
                                onChange={(event) =>
                                    onChange({ name: event.target.value })
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="entity-code">
                                {t('code', { ns: 'common' })}
                            </Label>
                            <Input
                                id="entity-code"
                                value={state.code}
                                onChange={(event) =>
                                    onChange({ code: event.target.value })
                                }
                                required
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                {t('cancel', { ns: 'common' })}
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={busy}>
                            {t(submitKey)}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
