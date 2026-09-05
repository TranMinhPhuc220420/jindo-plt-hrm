import { Link } from '@inertiajs/react';
import { ClockIcon, InfoIcon, Trash2Icon, UserPlusIcon } from 'lucide-react';
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
import { DatePicker } from '@/components/shared/date-picker';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { Shift, ShiftAssignment } from '@/lib/api/modules/shifts';
import { shiftKindLabel } from '@/lib/i18n/shift-labels';
import { cn } from '@/lib/utils';

type Props = {
    id: number;
};

const WEEKDAY_OPTIONS = [
    { value: 1, key: 'weekday_mon' },
    { value: 2, key: 'weekday_tue' },
    { value: 3, key: 'weekday_wed' },
    { value: 4, key: 'weekday_thu' },
    { value: 5, key: 'weekday_fri' },
    { value: 6, key: 'weekday_sat' },
    { value: 0, key: 'weekday_sun' },
] as const;

const PRESET_WEEKDAYS = [1, 2, 3, 4, 5];
const PRESET_MWF = [1, 3, 5];

export default function ShiftShowPage({ id }: Props) {
    const { t } = useTranslation(['shifts', 'common']);
    const [shift, setShift] = useState<Shift | null>(null);
    const [assignments, setAssignments] = useState<ShiftAssignment[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [employeeId, setEmployeeId] = useState<number | null>(null);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [everyDay, setEveryDay] = useState(false);
    const [weekdays, setWeekdays] = useState<number[]>([...PRESET_WEEKDAYS]);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [shiftData, assignmentData] = await Promise.all([
                shiftApi.getShift(id),
                shiftApi.listShiftAssignments({ shift_id: id, per_page: 50 }),
            ]);
            setShift(shiftData);
            setAssignments(assignmentData.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useLoadEffect(load, [load]);

    async function handleAssign(event: FormEvent) {
        event.preventDefault();

        if (employeeId === null) {
            return;
        }

        try {
            await shiftApi.createShiftAssignment({
                employee_id: employeeId,
                shift_id: id,
                start_date: startDate,
                end_date: endDate || null,
                weekdays: everyDay ? null : weekdays,
            });
            toast.success(t('show.toast_assigned'));
            setEmployeeId(null);
            setStartDate('');
            setEndDate('');
            setEveryDay(false);
            setWeekdays([...PRESET_WEEKDAYS]);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_assign_failed'),
            );
        }
    }

    async function handleRemoveAssignment(assignmentId: number) {
        try {
            await shiftApi.deleteShiftAssignment(assignmentId);
            toast.success(t('show.toast_removed'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_assign_failed'),
            );
        }
    }

    function applyPreset(days: number[]) {
        setEveryDay(false);
        setWeekdays(days);
    }

    function toggleWeekday(day: number) {
        if (everyDay) {
            return;
        }

        setWeekdays((current) =>
            current.includes(day)
                ? current.filter((value) => value !== day)
                : [...current, day],
        );
    }

    const description = shift
        ? `${shift.code} · ${shift.start_time}–${shift.end_time} · ${shiftKindLabel(t, shift.kind)}`
        : t('show.fallback_description');

    const canSubmit =
        employeeId !== null &&
        Boolean(startDate) &&
        (everyDay || weekdays.length > 0);

    return (
        <AdminPageShell
            title={
                shift
                    ? t('show.assign_title', { name: shift.name })
                    : t('show.fallback_title')
            }
            description={description}
            permission="can_view_shifts"
        >
            <div className="mb-4">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/shifts">{t('show.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('show.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : shift ? (
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,40rem)_minmax(16rem,1fr)]">
                    <Card className="gap-0 py-0 shadow-sm">
                        <CardHeader className="border-b px-5 py-4">
                            <div className="flex items-start gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <ClockIcon className="size-5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <CardTitle className="text-base">
                                        {shift.code} — {shift.name}
                                    </CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {shift.start_time} – {shift.end_time}
                                        {' · '}
                                        {t('index.break_minutes_value', {
                                            count: shift.break_minutes,
                                        })}
                                    </p>
                                </div>
                                <Badge variant="secondary">
                                    {shiftKindLabel(t, shift.kind)}
                                </Badge>
                            </div>
                        </CardHeader>
                        <PermissionGate permission="can_assign_shifts">
                            <form onSubmit={handleAssign}>
                                <CardContent className="grid gap-4 px-5 py-4">
                                    <Alert className="bg-muted/40">
                                        <InfoIcon />
                                        <AlertTitle>
                                            {t('show.this_shift_hint_title')}
                                        </AlertTitle>
                                        <AlertDescription>
                                            <p>{t('show.this_shift_hint')}</p>
                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="h-auto px-0"
                                                asChild
                                            >
                                                <Link href="/shifts">
                                                    {t('show.other_shifts')}
                                                </Link>
                                            </Button>
                                        </AlertDescription>
                                    </Alert>
                                    <EmployeePickerField
                                        id="employee_id"
                                        label={t('show.employee')}
                                        value={employeeId}
                                        onChange={(empId) =>
                                            setEmployeeId(empId)
                                        }
                                        required
                                        className="w-full"
                                    />
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="space-y-1">
                                            <Label htmlFor="start_date">
                                                {t('show.start_date')}
                                            </Label>
                                            <DatePicker
                                                id="start_date"
                                                value={startDate}
                                                onChange={setStartDate}
                                                required
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="end_date">
                                                {t('show.end_date')}
                                            </Label>
                                            <DatePicker
                                                id="end_date"
                                                value={endDate}
                                                onChange={setEndDate}
                                                min={startDate || undefined}
                                            />
                                        </div>
                                    </div>
                                    <fieldset className="grid gap-2.5">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <legend className="text-sm font-medium">
                                                {t('show.weekdays_label')}
                                            </legend>
                                            <div className="flex flex-wrap gap-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                    onClick={() =>
                                                        applyPreset(
                                                            PRESET_WEEKDAYS,
                                                        )
                                                    }
                                                >
                                                    {t('show.preset_weekdays')}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                    onClick={() =>
                                                        applyPreset(PRESET_MWF)
                                                    }
                                                >
                                                    {t('show.preset_mwf')}
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-1.5">
                                            <button
                                                type="button"
                                                className={cn(
                                                    'rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                                    !everyDay
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-input bg-background hover:bg-muted',
                                                )}
                                                onClick={() =>
                                                    setEveryDay(false)
                                                }
                                            >
                                                {t(
                                                    'show.weekdays_mode_selected',
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                className={cn(
                                                    'rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                                    everyDay
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-input bg-background hover:bg-muted',
                                                )}
                                                onClick={() =>
                                                    setEveryDay(true)
                                                }
                                            >
                                                {t('show.weekdays_mode_every')}
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-7 gap-1.5">
                                            {WEEKDAY_OPTIONS.map((day) => {
                                                const selected =
                                                    everyDay ||
                                                    weekdays.includes(
                                                        day.value,
                                                    );

                                                return (
                                                    <button
                                                        key={day.value}
                                                        type="button"
                                                        disabled={everyDay}
                                                        aria-pressed={selected}
                                                        onClick={() =>
                                                            toggleWeekday(
                                                                day.value,
                                                            )
                                                        }
                                                        className={cn(
                                                            'h-10 rounded-lg border text-xs font-semibold transition-colors sm:text-sm',
                                                            selected
                                                                ? 'border-primary bg-primary text-primary-foreground'
                                                                : 'border-input bg-background hover:bg-muted',
                                                            everyDay &&
                                                                'cursor-default opacity-80',
                                                        )}
                                                    >
                                                        {t(day.key, {
                                                            ns: 'common',
                                                        })}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {t('show.weekdays_help')}
                                        </p>
                                    </fieldset>
                                </CardContent>
                                <CardFooter className="border-t px-5 py-4">
                                    <Button
                                        type="submit"
                                        className="w-full sm:w-auto"
                                        disabled={!canSubmit}
                                    >
                                        <UserPlusIcon />
                                        {t('show.assign')}
                                    </Button>
                                </CardFooter>
                            </form>
                        </PermissionGate>
                    </Card>

                    <section className="grid gap-3 xl:sticky xl:top-4">
                        <div className="flex items-baseline justify-between gap-2">
                            <h2 className="text-lg font-medium">
                                {t('show.section_assigned_list')}
                            </h2>
                            {assignments.length > 0 ? (
                                <span className="text-sm text-muted-foreground">
                                    {t('show.assigned_count', {
                                        count: assignments.length,
                                    })}
                                </span>
                            ) : null}
                        </div>
                        {assignments.length === 0 ? (
                            <EmptyState message={t('show.empty_assignments')} />
                        ) : (
                            <ul className="grid gap-2">
                                {assignments.map((row) => (
                                    <li
                                        key={row.id}
                                        className="rounded-xl border bg-card px-3 py-3 shadow-sm"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0 space-y-1.5">
                                                <Link
                                                    href={`/employees/${row.employee_id}`}
                                                    className="font-medium text-primary-brand hover:underline"
                                                >
                                                    #
                                                    {row.employee?.code ??
                                                        row.employee_id}
                                                    {row.employee?.full_name
                                                        ? ` — ${row.employee.full_name}`
                                                        : ''}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {row.start_date}
                                                    {row.end_date
                                                        ? ` → ${row.end_date}`
                                                        : ` → ${t('show.open_end')}`}
                                                </p>
                                                <div className="flex flex-wrap gap-1">
                                                    {row.weekdays == null ? (
                                                        <Badge variant="secondary">
                                                            {t(
                                                                'show.every_day',
                                                            )}
                                                        </Badge>
                                                    ) : (
                                                        WEEKDAY_OPTIONS.filter(
                                                            (day) =>
                                                                row.weekdays?.includes(
                                                                    day.value,
                                                                ),
                                                        ).map((day) => (
                                                            <Badge
                                                                key={day.value}
                                                                variant="outline"
                                                            >
                                                                {t(day.key, {
                                                                    ns: 'common',
                                                                })}
                                                            </Badge>
                                                        ))
                                                    )}
                                                </div>
                                            </div>
                                            <PermissionGate permission="can_assign_shifts">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="shrink-0 text-muted-foreground hover:text-destructive"
                                                    onClick={() =>
                                                        void handleRemoveAssignment(
                                                            row.id,
                                                        )
                                                    }
                                                    aria-label={t('delete', {
                                                        ns: 'common',
                                                    })}
                                                >
                                                    <Trash2Icon />
                                                </Button>
                                            </PermissionGate>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            ) : null}
        </AdminPageShell>
    );
}
