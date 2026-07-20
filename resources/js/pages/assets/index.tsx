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
import * as assetsApi from '@/lib/api/modules/assets';
import type { Asset } from '@/lib/api/modules/assets';

function emptyCreateForm() {
    return { code: '', name: '', category: '', serial: '' };
}

export default function AssetsIndexPage() {
    const { t } = useTranslation(['assets', 'common']);
    const [assets, setAssets] = useState<Asset[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [filterStatus, setFilterStatus] = useState('');

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyCreateForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await assetsApi.listAssets({
                status: filterStatus || undefined,
                per_page: 50,
            });
            setAssets(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [filterStatus, t]);

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

    async function handleCreate(event: FormEvent) {
        event.preventDefault();
        setBusy(true);

        try {
            await assetsApi.createAsset({
                code: form.code,
                name: form.name,
                category: form.category || undefined,
                serial_number: form.serial || undefined,
            });
            toast.success(t('index.toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            any={['can_view_assets', 'can_manage_assets']}
            actions={
                <PermissionGate permission="can_manage_assets">
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('index.create')}
                    </Button>
                </PermissionGate>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('index.create_title')}</DialogTitle>
                        <DialogDescription>
                            {t('index.create_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="asset-code">
                                    {t('index.code')}
                                </Label>
                                <Input
                                    id="asset-code"
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
                            <div className="grid gap-2">
                                <Label htmlFor="asset-name">
                                    {t('index.name')}
                                </Label>
                                <Input
                                    id="asset-name"
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
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="asset-category">
                                    {t('index.category')}
                                </Label>
                                <Input
                                    id="asset-category"
                                    value={form.category}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            category: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="asset-serial">
                                    {t('index.serial')}
                                </Label>
                                <Input
                                    id="asset-serial"
                                    value={form.serial}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            serial: e.target.value,
                                        }))
                                    }
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
                                {t('index.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="mb-4 grid max-w-xs gap-1">
                <Label htmlFor="filter_status">
                    {t('index.filter_status')}
                </Label>
                <select
                    id="filter_status"
                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    value={filterStatus}
                    onChange={(e) => setFilterStatus(e.target.value)}
                >
                    <option value="">{t('index.all')}</option>
                    {(
                        [
                            'available',
                            'assigned',
                            'maintenance',
                            'retired',
                            'lost',
                        ] as const
                    ).map((status) => (
                        <option key={status} value={status}>
                            {t(`status.${status}`)}
                        </option>
                    ))}
                </select>
            </div>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : assets.length === 0 ? (
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
                                    {t('index.col_category')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_status')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {assets.map((asset) => (
                                <tr
                                    key={asset.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3">{asset.code}</td>
                                    <td className="px-3 py-3">{asset.name}</td>
                                    <td className="px-3 py-3">
                                        {asset.category ?? '—'}
                                    </td>
                                    <td className="px-3 py-3">
                                        {t(`status.${asset.status}`, {
                                            defaultValue: asset.status,
                                        })}
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={`/assets/${asset.id}`}>
                                                {t('index.open')}
                                            </Link>
                                        </Button>
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
