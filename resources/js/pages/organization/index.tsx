import { ChevronDown } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ApiError } from '@/lib/api/errors';
import * as orgApi from '@/lib/api/modules/organization';
import type { OrganizationTree } from '@/lib/api/modules/organization';
import { useAuth } from '@/lib/auth/auth-context';
import OrganizationCompanyDialog from './organization-company-dialog';
import OrganizationDeleteDialog from './organization-delete-dialog';
import OrganizationEntityDialog from './organization-entity-dialog';
import OrganizationNodePanel from './organization-node-panel';
import OrganizationPositionsSection from './organization-positions-section';
import OrganizationTreeView from './organization-tree';
import {
    emptyEntityDialog,
    resolveOrgNode,
    type DeleteDialogState,
    type EntityDialogState,
    type OrgEntityKind,
    type OrgSelection,
    type ResolvedOrgNode,
} from './org-types';

export default function OrganizationPage() {
    const { t } = useTranslation(['organization', 'common']);
    const { can } = useAuth();
    const canManage = can('can_manage_organization');
    const canManageCompany = can('can_manage_company');

    const [tree, setTree] = useState<OrganizationTree | null>(null);
    const [companyEmail, setCompanyEmail] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [selection, setSelection] = useState<OrgSelection | null>(null);

    const [companyOpen, setCompanyOpen] = useState(false);
    const [companyFormName, setCompanyFormName] = useState('');
    const [companyFormEmail, setCompanyFormEmail] = useState('');

    const [entityDialog, setEntityDialog] = useState<EntityDialogState>(
        emptyEntityDialog(),
    );
    const [deleteDialog, setDeleteDialog] = useState<DeleteDialogState | null>(
        null,
    );

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [treeData, company] = await Promise.all([
                orgApi.getOrganizationTree(),
                orgApi.getCurrentCompany(),
            ]);
            setTree(treeData);
            setCompanyEmail(company.email ?? '');
            setCompanyFormName(company.name);
            setCompanyFormEmail(company.email ?? '');

            setSelection((current) => {
                if (!current) {
                    const firstBranch = treeData.branches[0];

                    return firstBranch
                        ? { kind: 'branch', id: firstBranch.id }
                        : null;
                }

                const stillExists = resolveOrgNode(treeData, current);

                if (stillExists) {
                    return current;
                }

                const firstBranch = treeData.branches[0];

                return firstBranch
                    ? { kind: 'branch', id: firstBranch.id }
                    : null;
            });
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useEffect(() => {
        void load();
    }, [load]);

    const selectedNode = useMemo(
        () => (tree ? resolveOrgNode(tree, selection) : null),
        [tree, selection],
    );

    async function runMutation(action: () => Promise<void>, success: string) {
        setBusy(true);

        try {
            await action();
            toast.success(success);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('request_failed', { ns: 'common' }),
            );
            throw err;
        } finally {
            setBusy(false);
        }
    }

    function openCreateDialog(
        kind: OrgEntityKind,
        defaults?: Partial<
            Pick<EntityDialogState, 'branchId' | 'departmentId'>
        >,
    ) {
        setEntityDialog({
            ...emptyEntityDialog(kind),
            open: true,
            mode: 'create',
            kind,
            branchId: defaults?.branchId ?? '',
            departmentId: defaults?.departmentId ?? '',
        });
    }

    function openEditDialog(node: ResolvedOrgNode) {
        setEntityDialog({
            open: true,
            mode: 'edit',
            kind: node.kind,
            id: node.id,
            name: node.name,
            code: node.code,
            branchId: node.branchId ?? '',
            departmentId: node.departmentId ?? '',
        });
    }

    function openEditPosition(position: OrganizationTree['positions'][number]) {
        setEntityDialog({
            open: true,
            mode: 'edit',
            kind: 'position',
            id: position.id,
            name: position.name,
            code: position.code,
            branchId: '',
            departmentId: '',
        });
    }

    function handleEntityOpenChange(open: boolean) {
        if (!open) {
            setEntityDialog(emptyEntityDialog(entityDialog.kind));

            return;
        }

        setEntityDialog((current) => ({ ...current, open }));
    }

    function openDeleteNode(node: ResolvedOrgNode) {
        setDeleteDialog({
            open: true,
            kind: node.kind,
            id: node.id,
            name: node.name,
        });
    }

    function openDeletePosition(
        position: OrganizationTree['positions'][number],
    ) {
        setDeleteDialog({
            open: true,
            kind: 'position',
            id: position.id,
            name: position.name,
        });
    }

    function handleAddChild(node: ResolvedOrgNode) {
        if (node.kind === 'branch') {
            openCreateDialog('department', { branchId: node.id });

            return;
        }

        if (node.kind === 'department') {
            openCreateDialog('team', { departmentId: node.id });
        }
    }

    function openCompanyDialog() {
        if (tree) {
            setCompanyFormName(tree.company.name);
            setCompanyFormEmail(companyEmail);
        }

        setCompanyOpen(true);
    }

    async function handleSaveCompany(event: FormEvent) {
        event.preventDefault();

        try {
            await runMutation(async () => {
                await orgApi.updateCurrentCompany({
                    name: companyFormName,
                    email: companyFormEmail || null,
                });
            }, t('toast_company_updated'));
            setCompanyOpen(false);
        } catch {
            // toast already shown
        }
    }

    async function handleEntitySubmit(event: FormEvent) {
        event.preventDefault();
        const dialog = entityDialog;

        try {
            if (dialog.mode === 'create') {
                if (dialog.kind === 'branch') {
                    await runMutation(async () => {
                        await orgApi.createBranch({
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_branch_created'));
                } else if (dialog.kind === 'department') {
                    const branchId = dialog.branchId;
                    if (branchId === '') {
                        toast.error(t('error_select_branch'));

                        return;
                    }

                    await runMutation(async () => {
                        await orgApi.createDepartment({
                            branch_id: branchId,
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_department_created'));
                } else if (dialog.kind === 'team') {
                    const departmentId = dialog.departmentId;
                    if (departmentId === '') {
                        toast.error(t('error_select_department'));

                        return;
                    }

                    await runMutation(async () => {
                        await orgApi.createTeam({
                            department_id: departmentId,
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_team_created'));
                } else {
                    await runMutation(async () => {
                        await orgApi.createPosition({
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_position_created'));
                }
            } else if (dialog.id !== undefined) {
                if (dialog.kind === 'branch') {
                    await runMutation(async () => {
                        await orgApi.updateBranch(dialog.id!, {
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_branch_updated'));
                } else if (dialog.kind === 'department') {
                    await runMutation(async () => {
                        await orgApi.updateDepartment(dialog.id!, {
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_department_updated'));
                } else if (dialog.kind === 'team') {
                    await runMutation(async () => {
                        await orgApi.updateTeam(dialog.id!, {
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_team_updated'));
                } else {
                    await runMutation(async () => {
                        await orgApi.updatePosition(dialog.id!, {
                            name: dialog.name.trim(),
                            code: dialog.code.trim(),
                        });
                    }, t('toast_position_updated'));
                }
            }

            handleEntityOpenChange(false);
        } catch {
            // toast already shown
        }
    }

    async function handleConfirmDelete() {
        if (!deleteDialog) {
            return;
        }

        const { kind, id } = deleteDialog;

        try {
            if (kind === 'branch') {
                await runMutation(async () => {
                    await orgApi.deleteBranch(id);
                }, t('toast_branch_deleted'));
            } else if (kind === 'department') {
                await runMutation(async () => {
                    await orgApi.deleteDepartment(id);
                }, t('toast_department_deleted'));
            } else if (kind === 'team') {
                await runMutation(async () => {
                    await orgApi.deleteTeam(id);
                }, t('toast_team_deleted'));
            } else {
                await runMutation(async () => {
                    await orgApi.deletePosition(id);
                }, t('toast_position_deleted'));
            }

            setDeleteDialog(null);
        } catch {
            // toast already shown
        }
    }

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            permission="can_view_organization"
            actions={
                <div className="flex flex-wrap items-center gap-2">
                    {canManageCompany && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={openCompanyDialog}
                        >
                            {t('edit_company')}
                        </Button>
                    )}
                    {canManage && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button">
                                    {t('add_menu')}
                                    <ChevronDown className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onClick={() => openCreateDialog('branch')}
                                >
                                    {t('add_branch')}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() =>
                                        openCreateDialog('department', {
                                            branchId:
                                                selectedNode?.kind === 'branch'
                                                    ? selectedNode.id
                                                    : selectedNode?.branchId ??
                                                      '',
                                        })
                                    }
                                >
                                    {t('add_department')}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() =>
                                        openCreateDialog('team', {
                                            departmentId:
                                                selectedNode?.kind ===
                                                'department'
                                                    ? selectedNode.id
                                                    : selectedNode?.departmentId ??
                                                      '',
                                        })
                                    }
                                >
                                    {t('add_team')}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() =>
                                        openCreateDialog('position')
                                    }
                                >
                                    {t('add_position')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            }
        >
            {loading && <LoadingState label={t('loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && tree && (
                <div className="space-y-6">
                    <div className="rounded-lg border border-border bg-muted/20 px-4 py-3">
                        <p className="text-sm font-semibold text-foreground">
                            {tree.company.name}
                            <span className="ml-2 font-normal text-muted-foreground">
                                ({tree.company.code})
                            </span>
                        </p>
                        {companyEmail ? (
                            <p className="mt-1 text-xs text-muted-foreground">
                                {companyEmail}
                            </p>
                        ) : null}
                    </div>

                    <div className="grid min-w-0 gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                        <div className="min-w-0 space-y-3">
                            <h3 className="text-sm font-semibold">
                                {t('section_structure')}
                            </h3>
                            <OrganizationTreeView
                                tree={tree}
                                selection={selection}
                                onSelect={(next) => {
                                    setSelection(next);

                                    if (
                                        typeof window !== 'undefined' &&
                                        window.matchMedia(
                                            '(max-width: 767px)',
                                        ).matches
                                    ) {
                                        requestAnimationFrame(() => {
                                            document
                                                .getElementById(
                                                    'organization-detail',
                                                )
                                                ?.scrollIntoView({
                                                    behavior: 'smooth',
                                                    block: 'start',
                                                });
                                        });
                                    }
                                }}
                                canManage={canManage}
                                onAddBranch={() => openCreateDialog('branch')}
                            />
                        </div>

                        <div
                            id="organization-detail"
                            className="min-w-0 rounded-lg border border-border p-4"
                        >
                            <h3 className="mb-3 text-sm font-semibold">
                                {t('section_detail')}
                            </h3>
                            <OrganizationNodePanel
                                node={selectedNode}
                                onEdit={() => {
                                    if (selectedNode) {
                                        openEditDialog(selectedNode);
                                    }
                                }}
                                onDelete={() => {
                                    if (selectedNode) {
                                        openDeleteNode(selectedNode);
                                    }
                                }}
                                onAddChild={() => {
                                    if (selectedNode) {
                                        handleAddChild(selectedNode);
                                    }
                                }}
                            />
                        </div>
                    </div>

                    <OrganizationPositionsSection
                        positions={tree.positions}
                        onAdd={() => openCreateDialog('position')}
                        onEdit={openEditPosition}
                        onDelete={openDeletePosition}
                    />
                </div>
            )}

            <PermissionGate permission="can_manage_company">
                <OrganizationCompanyDialog
                    open={companyOpen}
                    name={companyFormName}
                    email={companyFormEmail}
                    busy={busy}
                    onOpenChange={setCompanyOpen}
                    onNameChange={setCompanyFormName}
                    onEmailChange={setCompanyFormEmail}
                    onSubmit={handleSaveCompany}
                />
            </PermissionGate>

            {tree && (
                <OrganizationEntityDialog
                    state={entityDialog}
                    tree={tree}
                    busy={busy}
                    onOpenChange={handleEntityOpenChange}
                    onChange={(patch) =>
                        setEntityDialog((current) => ({
                            ...current,
                            ...patch,
                        }))
                    }
                    onSubmit={handleEntitySubmit}
                />
            )}

            <OrganizationDeleteDialog
                state={deleteDialog}
                busy={busy}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteDialog(null);
                    }
                }}
                onConfirm={() => {
                    void handleConfirmDelete();
                }}
            />
        </AdminPageShell>
    );
}
