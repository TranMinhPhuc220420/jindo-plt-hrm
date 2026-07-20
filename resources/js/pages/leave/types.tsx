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
import * as leaveApi from '@/lib/api/modules/leave';
import type { LeaveType } from '@/lib/api/modules/leave';

function emptyCreateForm() {
    return { code: '', name: '' };
}

export default function LeaveTypesPage() {
    const { t } = useTranslation(['leave', 'common']);
    const [types, setTypes] = useState<LeaveType[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyCreateForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await leaveApi.listLeaveTypes({ per_page: 50 });
            setTypes(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('types.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useLoadEffect(load, [load]);

    function resetCreateForm() {
        setForm(emptyCreateForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            resetCreateForm();
        }
    }

    async function handleCreate(e: FormEvent) {
        e.preventDefault();
        setBusy(true);

        try {
            await leaveApi.createLeaveType({
                code: form.code,
                name: form.name,
            });
            toast.success(t('types.toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('types.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={t('types.title')}
            description={t('types.description')}
            permission="can_manage_leave_types"
            actions={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/leave">{t('types.back')}</Link>
                    </Button>
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('types.create')}
                    </Button>
                </div>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('types.create')}</DialogTitle>
                        <DialogDescription>
                            {t('types.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="code">{t('types.code')}</Label>
                            <Input
                                id="code"
                                value={form.code}
                                onChange={(e) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        code: e.target.value,
                                    }))
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="name">{t('types.name')}</Label>
                            <Input
                                id="name"
                                value={form.name}
                                onChange={(e) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        name: e.target.value,
                                    }))
                                }
                                required
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={busy}>
                                {t('types.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {loading ? (
                <LoadingState label={t('types.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : types.length === 0 ? (
                <EmptyState message={t('types.empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('types.code')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('types.name')}
                                </th>
                                <th className="px-3 py-2 text-right font-medium">
                                    {t('status', { ns: 'common' })}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {types.map((row) => (
                                <tr
                                    key={row.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3 text-muted-foreground">
                                        {row.code}
                                    </td>
                                    <td className="px-3 py-3 font-medium">
                                        {row.name}
                                        <span className="ml-2 font-normal text-muted-foreground">
                                            · {row.unit_default}
                                        </span>
                                    </td>
                                    <td className="px-3 py-3 text-right text-muted-foreground">
                                        {row.is_active
                                            ? t('types.active')
                                            : t('types.inactive')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
