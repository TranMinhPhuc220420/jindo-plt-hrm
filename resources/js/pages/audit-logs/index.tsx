import { useCallback, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as auditApi from '@/lib/api/modules/audit';
import type { AuditLog } from '@/lib/api/modules/audit';
import {
    AUDIT_FILTER_ALL,
    AUDIT_SUBJECT_OPTIONS,
    auditActionLabel,
    auditActorLabel,
    auditActionsByDomain,
    auditSubjectLabel,
    auditSubjectTypeLabel,
} from '@/lib/i18n/audit-labels';

type Filters = {
    action: string;
    subject_type: string;
    date_from: string;
    date_to: string;
};

const EMPTY_FILTERS: Filters = {
    action: '',
    subject_type: '',
    date_from: '',
    date_to: '',
};

export default function AuditLogsPage() {
    const { t, i18n } = useTranslation(['audit', 'common']);
    const [logs, setLogs] = useState<AuditLog[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [filters, setFilters] = useState<Filters>(EMPTY_FILTERS);
    const [applied, setApplied] = useState<Filters>(EMPTY_FILTERS);
    const [selected, setSelected] = useState<AuditLog | null>(null);

    const actionGroups = useMemo(() => auditActionsByDomain(), []);

    const load = useCallback(
        async (pageNumber: number, active: Filters) => {
            setLoading(true);
            setError(null);

            try {
                const result = await auditApi.listAuditLogs({
                    page: pageNumber,
                    per_page: 20,
                    action: active.action || undefined,
                    subject_type: active.subject_type || undefined,
                    date_from: active.date_from || undefined,
                    date_to: active.date_to || undefined,
                });
                setLogs(result.data);
                setPage(result.meta?.current_page ?? pageNumber);
                setLastPage(result.meta?.last_page ?? 1);
            } catch (err) {
                setError(
                    err instanceof ApiError ? err.message : t('error_load'),
                );
            } finally {
                setLoading(false);
            }
        },
        [t],
    );

    useLoadEffect(() => {
        void load(1, applied);
    }, [load, applied]);

    function handleApply(e: React.FormEvent) {
        e.preventDefault();
        setSelected(null);
        setApplied(filters);
    }

    function handleReset() {
        setFilters(EMPTY_FILTERS);
        setSelected(null);
        setApplied(EMPTY_FILTERS);
    }

    const emptyValue = t('empty_value', { ns: 'common' });

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            permission="can_view_audit_logs"
        >
            <form
                onSubmit={handleApply}
                className="mb-4 grid gap-3 rounded-lg border border-border p-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div className="grid gap-1.5">
                    <Label htmlFor="filter_action">{t('filter_action')}</Label>
                    <Select
                        value={filters.action || AUDIT_FILTER_ALL}
                        onValueChange={(value) =>
                            setFilters((prev) => ({
                                ...prev,
                                action: value === AUDIT_FILTER_ALL ? '' : value,
                            }))
                        }
                    >
                        <SelectTrigger id="filter_action" className="w-full">
                            <SelectValue
                                placeholder={t('filter_action_placeholder')}
                            />
                        </SelectTrigger>
                        <SelectContent className="max-h-80">
                            <SelectItem value={AUDIT_FILTER_ALL}>
                                {t('filter_action_all')}
                            </SelectItem>
                            {actionGroups.map((group) => (
                                <SelectGroup key={group.domain}>
                                    <SelectLabel>
                                        {t(`filter_domain.${group.domain}`, {
                                            defaultValue: group.domain,
                                        })}
                                    </SelectLabel>
                                    {group.actions.map((code) => (
                                        <SelectItem key={code} value={code}>
                                            {auditActionLabel(t, code)}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="filter_subject">
                        {t('filter_subject_type')}
                    </Label>
                    <Select
                        value={filters.subject_type || AUDIT_FILTER_ALL}
                        onValueChange={(value) =>
                            setFilters((prev) => ({
                                ...prev,
                                subject_type:
                                    value === AUDIT_FILTER_ALL ? '' : value,
                            }))
                        }
                    >
                        <SelectTrigger id="filter_subject" className="w-full">
                            <SelectValue
                                placeholder={t('filter_subject_placeholder')}
                            />
                        </SelectTrigger>
                        <SelectContent className="max-h-80">
                            <SelectItem value={AUDIT_FILTER_ALL}>
                                {t('filter_subject_all')}
                            </SelectItem>
                            {AUDIT_SUBJECT_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.morph}
                                    value={option.morph}
                                >
                                    {auditSubjectTypeLabel(t, option.morph)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="grid gap-1.5 sm:col-span-2">
                    <Label htmlFor="filter_dates">
                        {t('filter_date_from')}
                        {' – '}
                        {t('filter_date_to')}
                    </Label>
                    <DateRangePicker
                        id="filter_dates"
                        from={filters.date_from}
                        to={filters.date_to}
                        onChange={({ from, to }) =>
                            setFilters((prev) => ({
                                ...prev,
                                date_from: from,
                                date_to: to,
                            }))
                        }
                        numberOfMonths={1}
                    />
                </div>
                <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <Button type="submit" size="sm">
                        {t('filter_apply')}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={handleReset}
                    >
                        {t('filter_reset')}
                    </Button>
                </div>
            </form>

            {loading && <LoadingState label={t('loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && (
                <div className="space-y-4">
                    {logs.length === 0 ? (
                        <EmptyState message={t('empty')} />
                    ) : (
                        <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">
                                                {t('col_when')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('col_action')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('col_actor')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('col_subject')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logs.map((log) => (
                                            <tr
                                                key={log.id}
                                                onClick={() => setSelected(log)}
                                                className={`cursor-pointer border-t border-border transition-colors hover:bg-muted/40 ${
                                                    selected?.id === log.id
                                                        ? 'bg-muted/60'
                                                        : ''
                                                }`}
                                            >
                                                <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                                    {log.created_at
                                                        ? new Date(
                                                              log.created_at,
                                                          ).toLocaleString(
                                                              i18n.language,
                                                          )
                                                        : emptyValue}
                                                </td>
                                                <td className="px-3 py-2 font-medium">
                                                    {auditActionLabel(
                                                        t,
                                                        log.action,
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {auditActorLabel(
                                                        t,
                                                        log.actor_id,
                                                        log.actor_type,
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {auditSubjectLabel(
                                                        t,
                                                        log.subject_type,
                                                        log.subject_id,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="rounded-lg border border-border p-4 text-sm">
                                {selected ? (
                                    <div className="space-y-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <h2 className="font-medium">
                                                {t('detail_title')}
                                            </h2>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setSelected(null)
                                                }
                                            >
                                                {t('detail_close')}
                                            </Button>
                                        </div>
                                        <dl className="grid gap-2">
                                            <div>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t('col_action')}
                                                </dt>
                                                <dd className="font-medium">
                                                    {auditActionLabel(
                                                        t,
                                                        selected.action,
                                                    )}
                                                </dd>
                                                {selected.action && (
                                                    <dd className="mt-0.5 text-xs text-muted-foreground">
                                                        {t(
                                                            'detail_action_code',
                                                        )}
                                                        : {selected.action}
                                                    </dd>
                                                )}
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t('col_when')}
                                                </dt>
                                                <dd>
                                                    {selected.created_at
                                                        ? new Date(
                                                              selected.created_at,
                                                          ).toLocaleString(
                                                              i18n.language,
                                                          )
                                                        : emptyValue}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t('col_actor')}
                                                </dt>
                                                <dd>
                                                    {auditActorLabel(
                                                        t,
                                                        selected.actor_id,
                                                        selected.actor_type,
                                                    )}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t('col_subject')}
                                                </dt>
                                                <dd>
                                                    {auditSubjectLabel(
                                                        t,
                                                        selected.subject_type,
                                                        selected.subject_id,
                                                    )}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t('detail_ip')}
                                                </dt>
                                                <dd>
                                                    {selected.ip_address ??
                                                        emptyValue}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="mb-1 text-xs text-muted-foreground uppercase">
                                                    {t('detail_payload')}
                                                </dt>
                                                <dd>
                                                    <pre className="max-h-80 overflow-auto rounded-md bg-muted/50 p-3 text-xs">
                                                        {selected.payload
                                                            ? JSON.stringify(
                                                                  selected.payload,
                                                                  null,
                                                                  2,
                                                              )
                                                            : t(
                                                                  'detail_no_payload',
                                                              )}
                                                    </pre>
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">
                                        {t('detail_hint')}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {lastPage > 1 && (
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page <= 1 || loading}
                                onClick={() => void load(page - 1, applied)}
                            >
                                {t('previous', { ns: 'common' })}
                            </Button>
                            <span className="text-sm text-muted-foreground">
                                {t('page_of', {
                                    ns: 'common',
                                    page,
                                    lastPage,
                                })}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page >= lastPage || loading}
                                onClick={() => void load(page + 1, applied)}
                            >
                                {t('next', { ns: 'common' })}
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </AdminPageShell>
    );
}
