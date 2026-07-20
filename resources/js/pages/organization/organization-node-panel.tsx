import { useTranslation } from 'react-i18next';
import { EmptyState } from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ResolvedOrgNode } from './org-types';

type Props = {
    node: ResolvedOrgNode | null;
    onEdit: () => void;
    onDelete: () => void;
    onAddChild: () => void;
};

export default function OrganizationNodePanel({
    node,
    onEdit,
    onDelete,
    onAddChild,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);

    if (!node) {
        return <EmptyState message={t('empty_select_node')} />;
    }

    const typeLabel = t(`type_${node.kind}`);
    const canAddChild = node.kind === 'branch' || node.kind === 'department';
    const addChildLabel =
        node.kind === 'branch'
            ? t('add_department')
            : node.kind === 'department'
              ? t('add_team')
              : null;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">{typeLabel}</Badge>
                        <h3 className="text-base font-semibold text-foreground">
                            {node.name}
                        </h3>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {t('code', { ns: 'common' })}: {node.code}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {node.path.join(' · ')}
                    </p>
                </div>

                <PermissionGate permission="can_manage_organization">
                    <div className="flex flex-wrap gap-2">
                        {canAddChild && addChildLabel && (
                            <Button
                                type="button"
                                size="sm"
                                onClick={onAddChild}
                            >
                                {addChildLabel}
                            </Button>
                        )}
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={onEdit}
                        >
                            {t('edit', { ns: 'common' })}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={onDelete}
                        >
                            {t('delete', { ns: 'common' })}
                        </Button>
                    </div>
                </PermissionGate>
            </div>

            <dl className="grid gap-3 rounded-lg border border-border bg-muted/20 p-4 text-sm sm:grid-cols-2">
                {node.kind === 'branch' && (
                    <>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('stat_departments')}
                            </dt>
                            <dd className="font-medium">
                                {node.departmentCount ?? 0}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('stat_teams')}
                            </dt>
                            <dd className="font-medium">
                                {node.teamCount ?? 0}
                            </dd>
                        </div>
                    </>
                )}
                {node.kind === 'department' && (
                    <>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('branch_label')}
                            </dt>
                            <dd className="font-medium">{node.branchName}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('stat_teams')}
                            </dt>
                            <dd className="font-medium">
                                {node.teamCount ?? 0}
                            </dd>
                        </div>
                    </>
                )}
                {node.kind === 'team' && (
                    <>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('branch_label')}
                            </dt>
                            <dd className="font-medium">{node.branchName}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                {t('department_label')}
                            </dt>
                            <dd className="font-medium">
                                {node.departmentName}
                            </dd>
                        </div>
                    </>
                )}
            </dl>

            {canAddChild &&
                ((node.kind === 'branch' &&
                    (node.departmentCount ?? 0) === 0) ||
                    (node.kind === 'department' &&
                        (node.teamCount ?? 0) === 0)) && (
                    <p className="text-sm text-muted-foreground">
                        {node.kind === 'branch'
                            ? t('helper_add_department')
                            : t('helper_add_team')}
                    </p>
                )}
        </div>
    );
}
