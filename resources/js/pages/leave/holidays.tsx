import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DatePicker } from '@/components/shared/date-picker';
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
import { ApiError } from '@/lib/api/errors';
import * as leaveApi from '@/lib/api/modules/leave';
import type { Holiday } from '@/lib/api/modules/leave';

const WEEKDAY_KEYS = [
    'weekday_sun',
    'weekday_mon',
    'weekday_tue',
    'weekday_wed',
    'weekday_thu',
    'weekday_fri',
    'weekday_sat',
] as const;

function emptyHolidayForm() {
    return { date: '', name: '' };
}

export default function LeaveHolidaysPage() {
    const { t } = useTranslation(['leave', 'common']);
    const [holidays, setHolidays] = useState<Holiday[]>([]);
    const [weekendDays, setWeekendDays] = useState<number[]>([0, 6]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyHolidayForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [holidayRows, rules] = await Promise.all([
                leaveApi.listHolidays(new Date().getFullYear()),
                leaveApi.getWeekendRules(),
            ]);
            setHolidays(holidayRows);
            setWeekendDays(rules?.weekend_days ?? [0, 6]);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('holidays.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useEffect(() => {
        void load();
    }, [load]);

    function resetHolidayForm() {
        setForm(emptyHolidayForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            resetHolidayForm();
        }
    }

    async function handleCreate(e: FormEvent) {
        e.preventDefault();
        setBusy(true);

        try {
            await leaveApi.createHoliday({
                date: form.date,
                name: form.name,
            });
            toast.success(t('holidays.toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('holidays.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDelete(id: number) {
        try {
            await leaveApi.deleteHoliday(id);
            toast.success(t('holidays.toast_deleted'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('holidays.toast_error'),
            );
        }
    }

    async function handleSaveWeekend() {
        setBusy(true);

        try {
            await leaveApi.updateWeekendRules(weekendDays);
            toast.success(t('holidays.toast_weekend'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('holidays.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    function toggleDay(day: number) {
        setWeekendDays((prev) =>
            prev.includes(day)
                ? prev.filter((d) => d !== day)
                : [...prev, day].sort(),
        );
    }

    return (
        <AdminPageShell
            title={t('holidays.title')}
            description={t('holidays.description')}
            permission="can_manage_holidays"
            actions={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/leave">{t('holidays.back')}</Link>
                    </Button>
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('holidays.create')}
                    </Button>
                </div>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('holidays.add_title')}</DialogTitle>
                        <DialogDescription>
                            {t('holidays.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="date">{t('holidays.date')}</Label>
                            <DatePicker
                                id="date"
                                value={form.date}
                                onChange={(date) =>
                                    setForm((prev) => ({ ...prev, date }))
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="name">{t('holidays.name')}</Label>
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
                                {t('holidays.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <section className="mb-8 max-w-lg rounded-lg border border-border p-4">
                <h2 className="mb-3 font-medium">
                    {t('holidays.weekend_title')}
                </h2>
                <div className="mb-3 flex flex-wrap gap-2">
                    {WEEKDAY_KEYS.map((key, day) => (
                        <Button
                            key={key}
                            type="button"
                            size="sm"
                            variant={
                                weekendDays.includes(day)
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => toggleDay(day)}
                        >
                            {t(key, { ns: 'common' })}
                        </Button>
                    ))}
                </div>
                <Button
                    type="button"
                    disabled={busy}
                    onClick={() => void handleSaveWeekend()}
                >
                    {t('holidays.save_weekend')}
                </Button>
            </section>

            {loading ? (
                <LoadingState label={t('holidays.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : holidays.length === 0 ? (
                <EmptyState message={t('holidays.empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('holidays.date')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('holidays.name')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {holidays.map((row) => (
                                <tr
                                    key={row.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3 text-muted-foreground">
                                        {row.date}
                                    </td>
                                    <td className="px-3 py-3 font-medium">
                                        {row.name}
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                void handleDelete(row.id)
                                            }
                                        >
                                            {t('holidays.delete')}
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
