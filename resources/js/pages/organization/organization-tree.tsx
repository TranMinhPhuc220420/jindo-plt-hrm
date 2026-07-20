import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { EmptyState } from '@/components/shared/async-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { OrganizationTree } from '@/lib/api/modules/organization';
import { cn } from '@/lib/utils';
import type { OrgSelection } from './org-types';

type Props = {
    tree: OrganizationTree;
    selection: OrgSelection | null;
    onSelect: (selection: OrgSelection) => void;
    canManage: boolean;
    onAddBranch: () => void;
};

function isSelected(
    selection: OrgSelection | null,
    kind: OrgSelection['kind'],
    id: number,
): boolean {
    return selection?.kind === kind && selection.id === id;
}

export default function OrganizationTree({
    tree,
    selection,
    onSelect,
    canManage,
    onAddBranch,
}: Props) {
    const { t } = useTranslation(['organization', 'common']);
    const [openBranches, setOpenBranches] = useState<Record<number, boolean>>(
        () =>
            Object.fromEntries(
                tree.branches.map((branch, index) => [branch.id, index === 0]),
            ),
    );
    const [openDepartments, setOpenDepartments] = useState<
        Record<number, boolean>
    >(() => {
        const firstBranch = tree.branches[0];

        if (!firstBranch) {
            return {};
        }

        return Object.fromEntries(
            firstBranch.departments.map((department) => [department.id, true]),
        );
    });

    if (tree.branches.length === 0) {
        return (
            <div className="space-y-3">
                <EmptyState message={t('empty_branches')} />
                {canManage && (
                    <Button type="button" size="sm" onClick={onAddBranch}>
                        {t('add_branch')}
                    </Button>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-1">
            {tree.branches.map((branch) => {
                const teamCount = branch.departments.reduce(
                    (sum, department) => sum + department.teams.length,
                    0,
                );
                const branchOpen = openBranches[branch.id] ?? false;

                return (
                    <Collapsible
                        key={branch.id}
                        open={branchOpen}
                        onOpenChange={(open) =>
                            setOpenBranches((current) => ({
                                ...current,
                                [branch.id]: open,
                            }))
                        }
                    >
                        <div
                            className={cn(
                                'flex items-center gap-1 rounded-md px-1 py-1',
                                isSelected(selection, 'branch', branch.id) &&
                                    'bg-primary-deep/10',
                            )}
                        >
                            <CollapsibleTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="size-7 shrink-0 p-0"
                                    aria-label={
                                        branchOpen ? t('collapse') : t('expand')
                                    }
                                >
                                    {branchOpen ? (
                                        <ChevronDown className="size-4" />
                                    ) : (
                                        <ChevronRight className="size-4" />
                                    )}
                                </Button>
                            </CollapsibleTrigger>
                            <button
                                type="button"
                                onClick={() =>
                                    onSelect({
                                        kind: 'branch',
                                        id: branch.id,
                                    })
                                }
                                className={cn(
                                    'flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted',
                                    isSelected(
                                        selection,
                                        'branch',
                                        branch.id,
                                    ) &&
                                        'bg-primary-deep text-white hover:bg-primary-deep',
                                )}
                            >
                                <Badge
                                    variant="secondary"
                                    className={cn(
                                        'shrink-0',
                                        isSelected(
                                            selection,
                                            'branch',
                                            branch.id,
                                        ) &&
                                            'border-white/30 bg-white/15 text-white',
                                    )}
                                >
                                    {t('type_branch')}
                                </Badge>
                                <span className="min-w-0 truncate font-medium">
                                    {branch.name}
                                </span>
                                <span
                                    className={cn(
                                        'ml-auto shrink-0 text-xs',
                                        isSelected(
                                            selection,
                                            'branch',
                                            branch.id,
                                        )
                                            ? 'text-white/80'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {t('counts_branch', {
                                        departments: branch.departments.length,
                                        teams: teamCount,
                                    })}
                                </span>
                            </button>
                        </div>

                        <CollapsibleContent className="ml-4 space-y-1 border-l border-border pl-2">
                            {branch.departments.length === 0 ? (
                                <p className="px-2 py-1 text-xs text-muted-foreground">
                                    {t('empty_departments')}
                                </p>
                            ) : (
                                branch.departments.map((department) => {
                                    const departmentOpen =
                                        openDepartments[department.id] ?? false;

                                    return (
                                        <Collapsible
                                            key={department.id}
                                            open={departmentOpen}
                                            onOpenChange={(open) =>
                                                setOpenDepartments(
                                                    (current) => ({
                                                        ...current,
                                                        [department.id]: open,
                                                    }),
                                                )
                                            }
                                        >
                                            <div
                                                className={cn(
                                                    'flex items-center gap-1 rounded-md px-1 py-0.5',
                                                    isSelected(
                                                        selection,
                                                        'department',
                                                        department.id,
                                                    ) && 'bg-primary-deep/10',
                                                )}
                                            >
                                                <CollapsibleTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        className="size-7 shrink-0 p-0"
                                                    >
                                                        {departmentOpen ? (
                                                            <ChevronDown className="size-4" />
                                                        ) : (
                                                            <ChevronRight className="size-4" />
                                                        )}
                                                    </Button>
                                                </CollapsibleTrigger>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onSelect({
                                                            kind: 'department',
                                                            id: department.id,
                                                        })
                                                    }
                                                    className={cn(
                                                        'flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted',
                                                        isSelected(
                                                            selection,
                                                            'department',
                                                            department.id,
                                                        ) &&
                                                            'bg-primary-deep text-white hover:bg-primary-deep',
                                                    )}
                                                >
                                                    <Badge
                                                        variant="outline"
                                                        className={cn(
                                                            'shrink-0',
                                                            isSelected(
                                                                selection,
                                                                'department',
                                                                department.id,
                                                            ) &&
                                                                'border-white/30 bg-white/15 text-white',
                                                        )}
                                                    >
                                                        {t('type_department')}
                                                    </Badge>
                                                    <span className="min-w-0 truncate">
                                                        {department.name}
                                                    </span>
                                                    <span
                                                        className={cn(
                                                            'ml-auto shrink-0 text-xs',
                                                            isSelected(
                                                                selection,
                                                                'department',
                                                                department.id,
                                                            )
                                                                ? 'text-white/80'
                                                                : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {t(
                                                            'counts_department',
                                                            {
                                                                teams: department
                                                                    .teams
                                                                    .length,
                                                            },
                                                        )}
                                                    </span>
                                                </button>
                                            </div>

                                            <CollapsibleContent className="ml-4 space-y-0.5 border-l border-border pl-2">
                                                {department.teams.length ===
                                                0 ? (
                                                    <p className="px-2 py-1 text-xs text-muted-foreground">
                                                        {t('empty_teams')}
                                                    </p>
                                                ) : (
                                                    department.teams.map(
                                                        (team) => (
                                                            <button
                                                                key={team.id}
                                                                type="button"
                                                                onClick={() =>
                                                                    onSelect({
                                                                        kind: 'team',
                                                                        id: team.id,
                                                                    })
                                                                }
                                                                className={cn(
                                                                    'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted',
                                                                    isSelected(
                                                                        selection,
                                                                        'team',
                                                                        team.id,
                                                                    ) &&
                                                                        'bg-primary-deep text-white hover:bg-primary-deep',
                                                                )}
                                                            >
                                                                <Badge
                                                                    variant="outline"
                                                                    className={cn(
                                                                        'shrink-0',
                                                                        isSelected(
                                                                            selection,
                                                                            'team',
                                                                            team.id,
                                                                        ) &&
                                                                            'border-white/30 bg-white/15 text-white',
                                                                    )}
                                                                >
                                                                    {t(
                                                                        'type_team',
                                                                    )}
                                                                </Badge>
                                                                <span className="min-w-0 truncate">
                                                                    {team.name}
                                                                </span>
                                                            </button>
                                                        ),
                                                    )
                                                )}
                                            </CollapsibleContent>
                                        </Collapsible>
                                    );
                                })
                            )}
                        </CollapsibleContent>
                    </Collapsible>
                );
            })}
        </div>
    );
}
