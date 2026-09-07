import { Link } from '@inertiajs/react';
import {
    addWeeks,
    eachDayOfInterval,
    endOfWeek,
    format,
    startOfWeek,
    subWeeks,
} from 'date-fns';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    PlusIcon,
    Trash2Icon,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { TimePicker } from '@/components/shared/time-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { WorkingCalendarWindow } from '@/lib/api/modules/shifts';
import { useAuth } from '@/lib/auth/auth-context';
import { dateFnsLocale, displayDate, formatDateString } from '@/lib/datetime';

const WEEK_STARTS_ON = 1 as const;

type DaySlot = {
    key: string;
    start: string;
    end: string;
};

type DayPlan = {
    date: string;
    recurring: WorkingCalendarWindow[];
    slots: DaySlot[];
};

function newSlot(): DaySlot {
    return {
        key: `slot-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        start: '',
        end: '',
    };
}

function weekRange(anchor: Date): { from: Date; to: Date } {
    return {
        from: startOfWeek(anchor, { weekStartsOn: WEEK_STARTS_ON }),
        to: endOfWeek(anchor, { weekStartsOn: WEEK_STARTS_ON }),
    };
}

function emptyWeek(anchor: Date): DayPlan[] {
    const { from, to } = weekRange(anchor);

    return eachDayOfInterval({ start: from, end: to }).map((day) => ({
        date: formatDateString(day),
        recurring: [],
        slots: [],
    }));
}

function employeeIdFromQuery(): number | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = new URLSearchParams(window.location.search).get('employee_id');
    const id = raw ? Number(raw) : Number.NaN;

    return Number.isFinite(id) && id > 0 ? id : null;
}

export default function ShiftAssignPage() {
    const { t, i18n } = useTranslation(['shifts', 'common']);
    const { can } = useAuth();
    const [employeeId, setEmployeeId] = useState<number | null>(
        employeeIdFromQuery,
    );
    const [weekAnchor, setWeekAnchor] = useState(() => new Date());
    const [days, setDays] = useState<DayPlan[]>(() => emptyWeek(new Date()));
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    const { from, to } = useMemo(() => weekRange(weekAnchor), [weekAnchor]);
    const fromStr = formatDateString(from);
    const toStr = formatDateString(to);
    const locale = dateFnsLocale(i18n.language);
    const canAssign = can('can_assign_shifts');

    const loadWeek = useCallback(async () => {
        if (employeeId === null) {
            setDays(emptyWeek(weekAnchor));

            return;
        }

        setLoading(true);

        try {
            const calendar = await shiftApi.getWorkingCalendar({
                employee_id: employeeId,
                date_from: fromStr,
                date_to: toStr,
            });
            const byDate = new Map(calendar.map((day) => [day.date, day]));

            setDays(
                emptyWeek(weekAnchor).map((plan) => {
                    const windows = byDate.get(plan.date)?.windows ?? [];
                    const recurring = windows.filter(
                        (window) => window.source !== 'adhoc',
                    );
                    const slots = windows
                        .filter((window) => window.source === 'adhoc')
                        .map((window) => ({
                            key: `existing-${window.assignment_id ?? window.start_time}`,
                            start: window.start_time,
                            end: window.end_time,
                        }));

                    return { ...plan, recurring, slots };
                }),
            );
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('assign.toast_load_failed'),
            );
            setDays(emptyWeek(weekAnchor));
        } finally {
            setLoading(false);
        }
    }, [employeeId, fromStr, toStr, t, weekAnchor]);

    useLoadEffect(() => {
        void loadWeek();
    }, [loadWeek]);

    function updateDay(date: string, updater: (plan: DayPlan) => DayPlan) {
        setDays((current) =>
            current.map((plan) => (plan.date === date ? updater(plan) : plan)),
        );
    }

    async function handleSave() {
        if (employeeId === null || !canAssign) {
            return;
        }

        const slots = days.flatMap((plan) =>
            plan.slots
                .filter((slot) => slot.start && slot.end)
                .map((slot) => ({
                    date: plan.date,
                    start_time: slot.start,
                    end_time: slot.end,
                })),
        );

        setSaving(true);

        try {
            await shiftApi.replaceFlexibleSchedule({
                employee_id: employeeId,
                date_from: fromStr,
                date_to: toStr,
                slots,
            });
            toast.success(t('assign.toast_saved'));
            await loadWeek();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('assign.toast_save_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleCopyPrevious() {
        if (employeeId === null) {
            return;
        }

        const previousFrom = formatDateString(subWeeks(from, 1));
        const previousTo = formatDateString(subWeeks(to, 1));

        try {
            const calendar = await shiftApi.getWorkingCalendar({
                employee_id: employeeId,
                date_from: previousFrom,
                date_to: previousTo,
            });
            const byWeekday = new Map<number, DaySlot[]>();

            for (const day of calendar) {
                const parsed = new Date(`${day.date}T00:00:00`);
                const weekday = parsed.getDay();
                const slots = (day.windows ?? [])
                    .filter((window) => window.source === 'adhoc')
                    .map((window) => ({
                        key: `copy-${day.date}-${window.start_time}`,
                        start: window.start_time,
                        end: window.end_time,
                    }));

                if (slots.length > 0) {
                    byWeekday.set(weekday, slots);
                }
            }

            setDays((current) =>
                current.map((plan) => {
                    const weekday = new Date(`${plan.date}T00:00:00`).getDay();
                    const copied = byWeekday.get(weekday) ?? [];

                    return {
                        ...plan,
                        slots: copied.map((slot) => ({
                            ...slot,
                            key: `${slot.key}-${plan.date}`,
                        })),
                    };
                }),
            );
            toast.success(t('assign.toast_copied'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('assign.toast_copy_failed'),
            );
        }
    }

    return (
        <AdminPageShell
            title={t('assign.title')}
            description={t('assign.description')}
            permission="can_view_shifts"
            actions={
                <Button variant="outline" size="sm" asChild>
                    <Link href="/shifts">{t('assign.back')}</Link>
                </Button>
            }
        >
            <div className="grid gap-6">
                <Card>
                    <CardHeader className="border-b pb-4">
                        <CardTitle className="text-base">
                            {t('assign.week_label', {
                                from: displayDate(fromStr, i18n.language),
                                to: displayDate(toStr, i18n.language),
                            })}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 pt-4">
                        <EmployeePickerField
                            value={employeeId}
                            onChange={(id) => setEmployeeId(id)}
                            label={t('assign.employee')}
                            required
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setWeekAnchor((d) => subWeeks(d, 1))
                                }
                            >
                                <ChevronLeftIcon className="size-4" />
                                {t('assign.prev_week')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setWeekAnchor(new Date())}
                            >
                                {t('assign.this_week')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setWeekAnchor((d) => addWeeks(d, 1))
                                }
                            >
                                {t('assign.next_week')}
                                <ChevronRightIcon className="size-4" />
                            </Button>
                            <PermissionGate permission="can_assign_shifts">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    disabled={employeeId === null}
                                    onClick={() => void handleCopyPrevious()}
                                >
                                    {t('assign.copy_previous')}
                                </Button>
                            </PermissionGate>
                        </div>
                    </CardContent>
                </Card>

                {employeeId === null ? (
                    <p className="text-sm text-muted-foreground">
                        {t('assign.pick_employee')}
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
                        {days.map((plan) => {
                            const parsed = new Date(`${plan.date}T00:00:00`);

                            return (
                                <Card
                                    key={plan.date}
                                    className="min-w-0 gap-0 py-0"
                                >
                                    <CardHeader className="border-b px-4 py-3">
                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                            {format(parsed, 'EEE', { locale })}
                                        </p>
                                        <CardTitle className="text-sm">
                                            {displayDate(
                                                plan.date,
                                                i18n.language,
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="grid gap-3 px-4 py-3">
                                        {plan.recurring.length > 0 ? (
                                            <div className="grid gap-1">
                                                <p className="text-xs text-muted-foreground">
                                                    {t('assign.recurring')}
                                                </p>
                                                {plan.recurring.map(
                                                    (window) => (
                                                        <Badge
                                                            key={`${window.assignment_id}-${window.start_time}`}
                                                            variant="secondary"
                                                            className="justify-start font-normal"
                                                        >
                                                            {window.start_time}–
                                                            {window.end_time}
                                                        </Badge>
                                                    ),
                                                )}
                                            </div>
                                        ) : null}

                                        {plan.slots.map((slot, index) => (
                                            <div
                                                key={slot.key}
                                                className="grid gap-2 rounded-lg border border-border p-2"
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <Label className="text-xs">
                                                        {t('assign.slot_n', {
                                                            n: index + 1,
                                                        })}
                                                    </Label>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        disabled={!canAssign}
                                                        onClick={() =>
                                                            updateDay(
                                                                plan.date,
                                                                (current) => ({
                                                                    ...current,
                                                                    slots: current.slots.filter(
                                                                        (
                                                                            item,
                                                                        ) =>
                                                                            item.key !==
                                                                            slot.key,
                                                                    ),
                                                                }),
                                                            )
                                                        }
                                                    >
                                                        <Trash2Icon className="size-3.5" />
                                                    </Button>
                                                </div>
                                                <TimePicker
                                                    value={slot.start}
                                                    minuteStep={15}
                                                    disabled={
                                                        !canAssign || loading
                                                    }
                                                    onChange={(start) =>
                                                        updateDay(
                                                            plan.date,
                                                            (current) => ({
                                                                ...current,
                                                                slots: current.slots.map(
                                                                    (item) =>
                                                                        item.key ===
                                                                        slot.key
                                                                            ? {
                                                                                  ...item,
                                                                                  start,
                                                                              }
                                                                            : item,
                                                                ),
                                                            }),
                                                        )
                                                    }
                                                />
                                                <TimePicker
                                                    value={slot.end}
                                                    minuteStep={15}
                                                    disabled={
                                                        !canAssign || loading
                                                    }
                                                    onChange={(end) =>
                                                        updateDay(
                                                            plan.date,
                                                            (current) => ({
                                                                ...current,
                                                                slots: current.slots.map(
                                                                    (item) =>
                                                                        item.key ===
                                                                        slot.key
                                                                            ? {
                                                                                  ...item,
                                                                                  end,
                                                                              }
                                                                            : item,
                                                                ),
                                                            }),
                                                        )
                                                    }
                                                />
                                            </div>
                                        ))}

                                        {plan.slots.length === 0 &&
                                        plan.recurring.length === 0 ? (
                                            <p className="text-xs text-muted-foreground">
                                                {t('assign.day_off')}
                                            </p>
                                        ) : null}

                                        <PermissionGate permission="can_assign_shifts">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    loading ||
                                                    plan.slots.length >= 4
                                                }
                                                onClick={() =>
                                                    updateDay(
                                                        plan.date,
                                                        (current) => ({
                                                            ...current,
                                                            slots: [
                                                                ...current.slots,
                                                                newSlot(),
                                                            ],
                                                        }),
                                                    )
                                                }
                                            >
                                                <PlusIcon className="size-3.5" />
                                                {t('assign.add_slot')}
                                            </Button>
                                        </PermissionGate>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                <PermissionGate permission="can_assign_shifts">
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            type="button"
                            disabled={employeeId === null || saving || loading}
                            onClick={() => void handleSave()}
                        >
                            {saving ? t('assign.saving') : t('assign.save')}
                        </Button>
                    </div>
                </PermissionGate>
            </div>
        </AdminPageShell>
    );
}
