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
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as assetsApi from '@/lib/api/modules/assets';
import type { Asset, AssetMaintenance } from '@/lib/api/modules/assets';

type Props = {
    id: number;
};

export default function AssetShowPage({ id }: Props) {
    const { t } = useTranslation(['assets', 'common']);
    const [asset, setAsset] = useState<Asset | null>(null);
    const [maintenances, setMaintenances] = useState<AssetMaintenance[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [assignEmployeeId, setAssignEmployeeId] = useState<number | null>(
        null,
    );
    const [assignNote, setAssignNote] = useState('');
    const [returnCondition, setReturnCondition] = useState('');
    const [damageDescription, setDamageDescription] = useState('');
    const [maintDescription, setMaintDescription] = useState('');
    const [maintCost, setMaintCost] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const assetData = await assetsApi.getAsset(id);
            setAsset(assetData ?? null);

            try {
                const list = await assetsApi.listMaintenances(id);
                setMaintenances(list);
            } catch {
                setMaintenances([]);
            }
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useLoadEffect(load, [load]);

    async function withBusy(fn: () => Promise<void>) {
        setBusy(true);

        try {
            await fn();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('show.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleAssign(event: FormEvent) {
        event.preventDefault();

        if (assignEmployeeId === null) {
            return;
        }

        await withBusy(async () => {
            await assetsApi.assignAsset(id, {
                employee_id: assignEmployeeId,
                note: assignNote || undefined,
            });
            toast.success(t('show.toast_assigned'));
            setAssignEmployeeId(null);
            setAssignNote('');
            await load();
        });
    }

    async function handleReturn() {
        await withBusy(async () => {
            await assetsApi.returnAsset(id, {
                condition: returnCondition || undefined,
            });
            toast.success(t('show.toast_returned'));
            setReturnCondition('');
            await load();
        });
    }

    async function handleDamage(event: FormEvent) {
        event.preventDefault();
        await withBusy(async () => {
            await assetsApi.reportDamage(id, {
                description: damageDescription,
            });
            toast.success(t('show.toast_damage'));
            setDamageDescription('');
        });
    }

    async function handleMaintenance(event: FormEvent) {
        event.preventDefault();
        await withBusy(async () => {
            await assetsApi.createMaintenance(id, {
                description: maintDescription,
                cost: maintCost ? Number(maintCost) : undefined,
            });
            toast.success(t('show.toast_maintenance'));
            setMaintDescription('');
            setMaintCost('');
            await load();
        });
    }

    return (
        <AdminPageShell
            title={asset?.name ?? t('show.title')}
            description={t('show.description')}
            any={['can_view_assets', 'can_manage_assets']}
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/assets">{t('show.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('show.loading')} />
            ) : error || !asset ? (
                <ErrorState message={error ?? t('show.error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.code')}
                            </p>
                            <p>{asset.code}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.category')}
                            </p>
                            <p>{asset.category ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.status')}
                            </p>
                            <p>
                                {t(`status.${asset.status}`, {
                                    defaultValue: asset.status,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.serial')}
                            </p>
                            <p>{asset.serial_number ?? '—'}</p>
                        </div>
                    </div>

                    <PermissionGate permission="can_assign_asset">
                        <form
                            onSubmit={handleAssign}
                            className="grid max-w-xl gap-3 border-t border-border pt-6"
                        >
                            <h2 className="text-sm font-medium">
                                {t('show.assign_title')}
                            </h2>
                            <div className="grid gap-2 sm:grid-cols-2">
                                <EmployeePickerField
                                    id="assign_employee"
                                    label={t('show.employee_id')}
                                    value={assignEmployeeId}
                                    onChange={(id) => setAssignEmployeeId(id)}
                                    required
                                />
                                <div className="grid gap-2">
                                    <Label htmlFor="assign_note">
                                        {t('show.note')}
                                    </Label>
                                    <Input
                                        id="assign_note"
                                        value={assignNote}
                                        onChange={(e) =>
                                            setAssignNote(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <Button
                                type="submit"
                                disabled={
                                    busy ||
                                    asset.status !== 'available' ||
                                    assignEmployeeId === null
                                }
                            >
                                {t('show.assign')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <PermissionGate permission="can_return_asset">
                        <div className="grid max-w-xl gap-3 border-t border-border pt-6">
                            <h2 className="text-sm font-medium">
                                {t('show.return_title')}
                            </h2>
                            <div className="grid gap-2">
                                <Label htmlFor="return_condition">
                                    {t('show.condition')}
                                </Label>
                                <Input
                                    id="return_condition"
                                    value={returnCondition}
                                    onChange={(e) =>
                                        setReturnCondition(e.target.value)
                                    }
                                />
                            </div>
                            <Button
                                variant="secondary"
                                disabled={busy || asset.status !== 'assigned'}
                                onClick={() => void handleReturn()}
                            >
                                {t('show.return')}
                            </Button>
                        </div>
                    </PermissionGate>

                    <PermissionGate permission="can_report_asset_damage">
                        <form
                            onSubmit={handleDamage}
                            className="grid max-w-xl gap-3 border-t border-border pt-6"
                        >
                            <h2 className="text-sm font-medium">
                                {t('show.damage_title')}
                            </h2>
                            <div className="grid gap-2">
                                <Label htmlFor="damage_description">
                                    {t('show.description_label')}
                                </Label>
                                <Input
                                    id="damage_description"
                                    value={damageDescription}
                                    onChange={(e) =>
                                        setDamageDescription(e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={busy}
                            >
                                {t('show.report_damage')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <PermissionGate permission="can_manage_asset_maintenance">
                        <form
                            onSubmit={handleMaintenance}
                            className="grid max-w-xl gap-3 border-t border-border pt-6"
                        >
                            <h2 className="text-sm font-medium">
                                {t('show.maintenance_title')}
                            </h2>
                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="maint_description">
                                        {t('show.description_label')}
                                    </Label>
                                    <Input
                                        id="maint_description"
                                        value={maintDescription}
                                        onChange={(e) =>
                                            setMaintDescription(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="maint_cost">
                                        {t('show.cost')}
                                    </Label>
                                    <Input
                                        id="maint_cost"
                                        type="number"
                                        value={maintCost}
                                        onChange={(e) =>
                                            setMaintCost(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <Button type="submit" disabled={busy}>
                                {t('show.add_maintenance')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <div className="border-t border-border pt-6">
                        <h2 className="mb-3 text-sm font-medium">
                            {t('show.maintenance_history')}
                        </h2>
                        {maintenances.length === 0 ? (
                            <EmptyState message={t('show.empty_maintenance')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">
                                                {t('show.col_description')}
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                {t('show.col_status')}
                                            </th>
                                            <th className="py-2 font-medium">
                                                {t('show.col_cost')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {maintenances.map((m) => (
                                            <tr
                                                key={m.id}
                                                className="border-b border-border/60"
                                            >
                                                <td className="py-3 pr-4">
                                                    {m.description}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {m.status}
                                                </td>
                                                <td className="py-3">
                                                    {m.cost ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AdminPageShell>
    );
}
