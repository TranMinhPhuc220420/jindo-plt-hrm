import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import {
    emptyShiftDefinitionForm,
    ShiftDefinitionFormFields,
} from '@/components/shifts/shift-definition-form-fields';
import type { ShiftDefinitionFormValues } from '@/components/shifts/shift-definition-form-fields';
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
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { Shift } from '@/lib/api/modules/shifts';
import { useAuth } from '@/lib/auth/auth-context';
import { shiftKindLabel } from '@/lib/i18n/shift-labels';

function formFromShift(shift: Shift): ShiftDefinitionFormValues {
    return {
        name: shift.name,
        code: shift.code,
        startTime: shift.start_time,
        endTime: shift.end_time,
        breakMinutes: String(shift.break_minutes),
        kind: shift.kind,
        isNight: shift.is_night,
        isFlexible: shift.is_flexible,
    };
}

function payloadFromForm(form: ShiftDefinitionFormValues) {
    return {
        name: form.name,
        code: form.code,
        start_time: form.startTime,
        end_time: form.endTime,
        break_minutes: Number(form.breakMinutes) || 0,
        kind: form.kind,
        is_night: form.isNight,
        is_flexible: form.isFlexible,
    };
}

export default function ShiftsIndexPage() {
    const { t } = useTranslation(['shifts', 'common']);
    const { can } = useAuth();
    const [shifts, setShifts] = useState<Shift[]>([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [createForm, setCreateForm] = useState(emptyShiftDefinitionForm);
    const [editShift, setEditShift] = useState<Shift | null>(null);
    const [editForm, setEditForm] = useState(emptyShiftDefinitionForm);
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await shiftApi.listShifts({
                search: search || undefined,
                per_page: 50,
            });
            setShifts(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [search, t]);

    useLoadEffect(load, [load]);

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            setCreateForm(emptyShiftDefinitionForm());
        }
    }

    function openEdit(shift: Shift) {
        setEditShift(shift);
        setEditForm(formFromShift(shift));
    }

    function handleEditOpenChange(open: boolean) {
        if (!open) {
            setEditShift(null);
            setEditForm(emptyShiftDefinitionForm());
        }
    }

    async function handleCreate(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            await shiftApi.createShift(payloadFromForm(createForm));
            toast.success(t('create.toast_success'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('create.toast_error'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleEdit(event: FormEvent) {
        event.preventDefault();

        if (!editShift) {
            return;
        }

        setSaving(true);

        try {
            await shiftApi.updateShift(editShift.id, payloadFromForm(editForm));
            toast.success(t('edit.toast_success'));
            handleEditOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('edit.toast_error'),
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            permission="can_view_shifts"
            actions={
                <PermissionGate permission="can_manage_shift_definitions">
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('index.create')}
                    </Button>
                </PermissionGate>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('create.title')}</DialogTitle>
                        <DialogDescription>
                            {t('create.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <ShiftDefinitionFormFields
                            idPrefix="create-shift"
                            values={createForm}
                            onChange={setCreateForm}
                        />
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={saving}>
                                {t('index.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editShift !== null}
                onOpenChange={handleEditOpenChange}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('edit.title')}</DialogTitle>
                        <DialogDescription>
                            {t('edit.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEdit} className="grid gap-4">
                        <ShiftDefinitionFormFields
                            idPrefix="edit-shift"
                            values={editForm}
                            onChange={setEditForm}
                        />
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={saving}>
                                {t('edit.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="mb-4">
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void load();
                    }}
                >
                    <div className="space-y-1">
                        <Label htmlFor="search">
                            {t('search', { ns: 'common' })}
                        </Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t('index.search_placeholder')}
                            className="w-56"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        {t('search', { ns: 'common' })}
                    </Button>
                </form>
            </div>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : shifts.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_code')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_name')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_time')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_break')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_kind')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_flags')}
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    {t('index.col_actions')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {shifts.map((shift) => {
                                const flags = [
                                    shift.is_night
                                        ? t('create.is_night')
                                        : null,
                                    shift.is_flexible
                                        ? t('create.is_flexible')
                                        : null,
                                    !shift.is_active
                                        ? t('index.inactive')
                                        : null,
                                ].filter(Boolean);

                                return (
                                    <tr
                                        key={shift.id}
                                        className="border-t border-border hover:bg-muted/30"
                                    >
                                        <td className="px-3 py-2 font-medium">
                                            {shift.code}
                                        </td>
                                        <td className="px-3 py-2">
                                            {shift.name}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            {shift.start_time} –{' '}
                                            {shift.end_time}
                                        </td>
                                        <td className="px-3 py-2">
                                            {t('index.break_minutes_value', {
                                                count: shift.break_minutes,
                                            })}
                                        </td>
                                        <td className="px-3 py-2">
                                            {shiftKindLabel(t, shift.kind)}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {flags.length > 0
                                                ? flags.join(' · ')
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-2">
                                            <div className="flex flex-wrap items-center justify-end gap-2">
                                                {can(
                                                    'can_manage_shift_definitions',
                                                ) ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(shift)
                                                        }
                                                    >
                                                        {t('edit', {
                                                            ns: 'common',
                                                        })}
                                                    </Button>
                                                ) : null}
                                                <Button
                                                    variant="default"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/shifts/${shift.id}`}
                                                    >
                                                        {t(
                                                            'index.assign_action',
                                                        )}
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
