import { useTranslation } from 'react-i18next';
import { EmptyState } from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import type { OrganizationTree } from '@/lib/api/modules/organization';

type PositionRow = OrganizationTree['positions'][number];

type Props = {
    positions: PositionRow[];
    onAdd: () => void;
    onEdit: (position: PositionRow) => void;
    onDelete: (position: PositionRow) => void;
};

export default function OrganizationPositionsSection({
    positions,
    onAdd,
    onEdit,
    onDelete,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);

    return (
        <section className="space-y-3 border-t border-border pt-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold">
                        {t('section_positions')}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        {t('positions_description')}
                    </p>
                </div>
                <PermissionGate permission="can_manage_organization">
                    <Button type="button" size="sm" onClick={onAdd}>
                        {t('add_position')}
                    </Button>
                </PermissionGate>
            </div>

            {positions.length === 0 ? (
                <EmptyState message={t('empty_positions')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('name', { ns: 'common' })}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('code', { ns: 'common' })}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {positions.map((position) => (
                                <tr
                                    key={position.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3">
                                        {position.name}
                                    </td>
                                    <td className="px-3 py-3 text-muted-foreground">
                                        {position.code}
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <PermissionGate permission="can_manage_organization">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        onEdit(position)
                                                    }
                                                >
                                                    {t('edit', {
                                                        ns: 'common',
                                                    })}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        onDelete(position)
                                                    }
                                                >
                                                    {t('delete', {
                                                        ns: 'common',
                                                    })}
                                                </Button>
                                            </div>
                                        </PermissionGate>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
