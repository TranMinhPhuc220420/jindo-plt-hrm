import { useCallback, useRef, useState } from 'react';
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
import { Badge } from '@/components/ui/badge';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { useIsMobile } from '@/hooks/use-mobile';
import { ApiError } from '@/lib/api/errors';
import * as documentsApi from '@/lib/api/modules/documents';
import type {
    Document,
    DocumentCategory,
    DocumentOwnerType,
} from '@/lib/api/modules/documents';

const OWNER_TYPES: DocumentOwnerType[] = ['company', 'employee', 'candidate'];
const CATEGORIES: DocumentCategory[] = [
    'policy',
    'template',
    'contract',
    'certificate',
    'other',
];

function emptyUploadForm() {
    return {
        ownerType: 'company' as DocumentOwnerType,
        ownerId: null as number | null,
        candidateOwnerId: '',
        category: 'policy' as DocumentCategory,
        title: '',
    };
}

export default function DocumentsIndexPage() {
    const { t } = useTranslation(['documents', 'common']);
    const isMobile = useIsMobile();
    const [documents, setDocuments] = useState<Document[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [filterOwner, setFilterOwner] = useState<'' | DocumentOwnerType>('');
    const [filterCategory, setFilterCategory] = useState('');

    const [uploadOpen, setUploadOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [form, setForm] = useState(emptyUploadForm);
    const [busy, setBusy] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await documentsApi.listDocuments({
                owner_type: filterOwner || undefined,
                category: filterCategory || undefined,
                per_page: 50,
            });
            setDocuments(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [filterOwner, filterCategory, t]);

    useLoadEffect(load, [load]);

    function resetUploadForm() {
        setFile(null);
        setForm(emptyUploadForm());

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handleUploadOpenChange(open: boolean) {
        setUploadOpen(open);

        if (!open) {
            resetUploadForm();
        }
    }

    async function handleUpload(event: FormEvent) {
        event.preventDefault();

        if (!file) {
            toast.error(t('index.file_required'));

            return;
        }

        setBusy(true);

        try {
            const resolvedOwnerId =
                form.ownerType === 'company'
                    ? undefined
                    : form.ownerType === 'employee'
                      ? (form.ownerId ?? undefined)
                      : Number(form.candidateOwnerId) || undefined;

            await documentsApi.uploadDocument({
                file,
                owner_type: form.ownerType,
                owner_id: resolvedOwnerId,
                category: form.category,
                title: form.title || undefined,
            });
            toast.success(t('index.toast_uploaded'));
            handleUploadOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDownload(doc: Document) {
        try {
            await documentsApi.downloadDocument(doc);
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        }
    }

    async function handleDelete(doc: Document) {
        setBusy(true);

        try {
            await documentsApi.deleteDocument(doc.id);
            toast.success(t('index.toast_deleted'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    const canSubmitUpload =
        !busy &&
        !(form.ownerType === 'employee' && form.ownerId === null) &&
        !(form.ownerType === 'candidate' && !form.candidateOwnerId);

    const uploadFormFields = (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label htmlFor="file">{t('index.file')}</Label>
                <Input
                    id="file"
                    ref={fileInputRef}
                    type="file"
                    onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                    required
                    className="min-h-11 sm:min-h-9"
                />
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="owner_type">{t('index.owner_type')}</Label>
                    <select
                        id="owner_type"
                        className="h-11 rounded-md border border-input bg-background px-3 text-sm sm:h-9"
                        value={form.ownerType}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                ownerType: e.target.value as DocumentOwnerType,
                                ownerId: null,
                                candidateOwnerId: '',
                            }))
                        }
                    >
                        {OWNER_TYPES.map((type) => (
                            <option key={type} value={type}>
                                {t(`owner_type.${type}`)}
                            </option>
                        ))}
                    </select>
                </div>
                {form.ownerType === 'employee' ? (
                    <EmployeePickerField
                        id="owner_id"
                        label={t('index.owner_id')}
                        value={form.ownerId}
                        onChange={(id) =>
                            setForm((prev) => ({
                                ...prev,
                                ownerId: id,
                            }))
                        }
                        required
                    />
                ) : form.ownerType === 'candidate' ? (
                    <div className="grid gap-2">
                        <Label htmlFor="owner_id">{t('index.owner_id')}</Label>
                        <Input
                            id="owner_id"
                            type="number"
                            value={form.candidateOwnerId}
                            onChange={(e) =>
                                setForm((prev) => ({
                                    ...prev,
                                    candidateOwnerId: e.target.value,
                                }))
                            }
                            required
                            className="min-h-11 sm:min-h-9"
                        />
                    </div>
                ) : null}
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="category">{t('index.category')}</Label>
                    <select
                        id="category"
                        className="h-11 rounded-md border border-input bg-background px-3 text-sm sm:h-9"
                        value={form.category}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                category: e.target.value as DocumentCategory,
                            }))
                        }
                    >
                        {CATEGORIES.map((cat) => (
                            <option key={cat} value={cat}>
                                {t(`category.${cat}`)}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="title">{t('index.doc_title')}</Label>
                    <Input
                        id="title"
                        value={form.title}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                title: e.target.value,
                            }))
                        }
                        className="min-h-11 sm:min-h-9"
                    />
                </div>
            </div>
        </div>
    );

    const uploadActions = (
        <>
            <Button
                type="button"
                variant="secondary"
                className="min-h-11"
                disabled={busy}
                onClick={() => handleUploadOpenChange(false)}
            >
                {t('cancel', { ns: 'common' })}
            </Button>
            <Button
                type="submit"
                form="documents-upload-form"
                className="min-h-11"
                disabled={!canSubmitUpload}
            >
                {t('index.upload')}
            </Button>
        </>
    );

    const uploadGate = (
        <PermissionGate
            any={[
                'can_manage_company_documents',
                'can_manage_employee_documents',
                'can_upload_own_documents',
            ]}
        >
            <Button
                type="button"
                size={isMobile ? 'lg' : 'default'}
                className={isMobile ? 'min-h-11 w-full' : undefined}
                onClick={() => setUploadOpen(true)}
            >
                {t('index.upload')}
            </Button>
        </PermissionGate>
    );

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            any={[
                'can_view_company_documents',
                'can_view_employee_documents',
                'can_upload_own_documents',
            ]}
            actions={isMobile ? undefined : uploadGate}
        >
            {isMobile ? <div className="mb-6">{uploadGate}</div> : null}

            {isMobile ? (
                <Sheet open={uploadOpen} onOpenChange={handleUploadOpenChange}>
                    <SheetContent
                        side="bottom"
                        className="flex max-h-[90vh] flex-col gap-0 overflow-hidden rounded-t-xl pb-[max(1rem,env(safe-area-inset-bottom))]"
                    >
                        <SheetHeader className="border-b border-border text-left">
                            <SheetTitle>{t('index.upload_title')}</SheetTitle>
                            <SheetDescription>
                                {t('index.description')}
                            </SheetDescription>
                        </SheetHeader>
                        <form
                            id="documents-upload-form"
                            onSubmit={handleUpload}
                            className="flex-1 overflow-y-auto p-4"
                        >
                            {uploadFormFields}
                        </form>
                        <SheetFooter className="flex-row gap-2 border-t border-border">
                            {uploadActions}
                        </SheetFooter>
                    </SheetContent>
                </Sheet>
            ) : (
                <Dialog open={uploadOpen} onOpenChange={handleUploadOpenChange}>
                    <DialogContent className="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>{t('index.upload_title')}</DialogTitle>
                            <DialogDescription>
                                {t('index.description')}
                            </DialogDescription>
                        </DialogHeader>
                        <form
                            id="documents-upload-form"
                            onSubmit={handleUpload}
                        >
                            {uploadFormFields}
                        </form>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    disabled={busy}
                                >
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                form="documents-upload-form"
                                disabled={!canSubmitUpload}
                            >
                                {t('index.upload')}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <div className="grid w-full gap-1 sm:w-auto">
                    <Label htmlFor="filter_owner">
                        {t('index.filter_owner')}
                    </Label>
                    <select
                        id="filter_owner"
                        className="h-11 w-full rounded-md border border-input bg-background px-3 text-sm sm:h-9 sm:min-w-[10rem]"
                        value={filterOwner}
                        onChange={(e) =>
                            setFilterOwner(
                                e.target.value as '' | DocumentOwnerType,
                            )
                        }
                    >
                        <option value="">{t('index.all')}</option>
                        {OWNER_TYPES.map((type) => (
                            <option key={type} value={type}>
                                {t(`owner_type.${type}`)}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid w-full gap-1 sm:w-auto">
                    <Label htmlFor="filter_category">
                        {t('index.filter_category')}
                    </Label>
                    <select
                        id="filter_category"
                        className="h-11 w-full rounded-md border border-input bg-background px-3 text-sm sm:h-9 sm:min-w-[10rem]"
                        value={filterCategory}
                        onChange={(e) => setFilterCategory(e.target.value)}
                    >
                        <option value="">{t('index.all')}</option>
                        {CATEGORIES.map((cat) => (
                            <option key={cat} value={cat}>
                                {t(`category.${cat}`)}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : documents.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : isMobile ? (
                <ul className="space-y-2">
                    {documents.map((doc) => (
                        <li key={doc.id}>
                            <div className="rounded-lg border border-border bg-card px-3 py-3 shadow-sm">
                                <div className="space-y-1.5">
                                    <p className="text-sm font-medium">
                                        {doc.title}
                                    </p>
                                    <div className="flex flex-wrap gap-1.5">
                                        <Badge variant="outline">
                                            {t(`category.${doc.category}`, {
                                                defaultValue: doc.category,
                                            })}
                                        </Badge>
                                        <Badge variant="secondary">
                                            {t(`owner_type.${doc.owner_type}`, {
                                                defaultValue: doc.owner_type,
                                            })}
                                            {doc.owner_id
                                                ? ` #${doc.owner_id}`
                                                : ''}
                                        </Badge>
                                    </div>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {doc.original_name}
                                    </p>
                                </div>
                                <div className="mt-3 flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="min-h-11 flex-1"
                                        onClick={() => void handleDownload(doc)}
                                    >
                                        {t('index.download')}
                                    </Button>
                                    <PermissionGate
                                        any={[
                                            'can_manage_company_documents',
                                            'can_manage_employee_documents',
                                            'can_upload_own_documents',
                                        ]}
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="min-h-11 flex-1"
                                            disabled={busy}
                                            onClick={() =>
                                                void handleDelete(doc)
                                            }
                                        >
                                            {t('index.delete')}
                                        </Button>
                                    </PermissionGate>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_title')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_owner')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_category')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_file')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {documents.map((doc) => (
                                <tr
                                    key={doc.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3">{doc.title}</td>
                                    <td className="px-3 py-3">
                                        {t(`owner_type.${doc.owner_type}`, {
                                            defaultValue: doc.owner_type,
                                        })}
                                        {doc.owner_id
                                            ? ` #${doc.owner_id}`
                                            : ''}
                                    </td>
                                    <td className="px-3 py-3">
                                        {t(`category.${doc.category}`, {
                                            defaultValue: doc.category,
                                        })}
                                    </td>
                                    <td className="px-3 py-3">
                                        {doc.original_name}
                                    </td>
                                    <td className="px-3 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    void handleDownload(doc)
                                                }
                                            >
                                                {t('index.download')}
                                            </Button>
                                            <PermissionGate
                                                any={[
                                                    'can_manage_company_documents',
                                                    'can_manage_employee_documents',
                                                    'can_upload_own_documents',
                                                ]}
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={busy}
                                                    onClick={() =>
                                                        void handleDelete(doc)
                                                    }
                                                >
                                                    {t('index.delete')}
                                                </Button>
                                            </PermissionGate>
                                        </div>
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
