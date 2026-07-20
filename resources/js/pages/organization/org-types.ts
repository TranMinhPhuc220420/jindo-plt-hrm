import type { OrganizationTree } from '@/lib/api/modules/organization';

export type OrgNodeKind = 'branch' | 'department' | 'team';

export type OrgEntityKind = OrgNodeKind | 'position';

export type OrgSelection = {
    kind: OrgNodeKind;
    id: number;
};

export type ResolvedOrgNode = {
    kind: OrgNodeKind;
    id: number;
    name: string;
    code: string;
    path: string[];
    departmentCount?: number;
    teamCount?: number;
    branchId?: number;
    departmentId?: number;
    branchName?: string;
    departmentName?: string;
};

export type EntityDialogMode = 'create' | 'edit';

export type EntityDialogState = {
    open: boolean;
    mode: EntityDialogMode;
    kind: OrgEntityKind;
    id?: number;
    name: string;
    code: string;
    branchId: number | '';
    departmentId: number | '';
};

export type DeleteDialogState = {
    open: boolean;
    kind: OrgEntityKind;
    id: number;
    name: string;
};

export function emptyEntityDialog(
    kind: OrgEntityKind = 'branch',
): EntityDialogState {
    return {
        open: false,
        mode: 'create',
        kind,
        name: '',
        code: '',
        branchId: '',
        departmentId: '',
    };
}

export function resolveOrgNode(
    tree: OrganizationTree,
    selection: OrgSelection | null,
): ResolvedOrgNode | null {
    if (!selection) {
        return null;
    }

    for (const branch of tree.branches) {
        if (selection.kind === 'branch' && branch.id === selection.id) {
            const teamCount = branch.departments.reduce(
                (sum, department) => sum + department.teams.length,
                0,
            );

            return {
                kind: 'branch',
                id: branch.id,
                name: branch.name,
                code: branch.code,
                path: [tree.company.name, branch.name],
                departmentCount: branch.departments.length,
                teamCount,
            };
        }

        for (const department of branch.departments) {
            if (
                selection.kind === 'department' &&
                department.id === selection.id
            ) {
                return {
                    kind: 'department',
                    id: department.id,
                    name: department.name,
                    code: department.code,
                    path: [tree.company.name, branch.name, department.name],
                    teamCount: department.teams.length,
                    branchId: branch.id,
                    branchName: branch.name,
                };
            }

            for (const team of department.teams) {
                if (selection.kind === 'team' && team.id === selection.id) {
                    return {
                        kind: 'team',
                        id: team.id,
                        name: team.name,
                        code: team.code,
                        path: [
                            tree.company.name,
                            branch.name,
                            department.name,
                            team.name,
                        ],
                        branchId: branch.id,
                        departmentId: department.id,
                        branchName: branch.name,
                        departmentName: department.name,
                    };
                }
            }
        }
    }

    return null;
}
