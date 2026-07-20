import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { TimePicker } from '@/components/shared/time-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as shiftApi from '@/lib/api/modules/shifts';
import { SHIFT_KINDS, shiftKindLabel } from '@/lib/i18n/shift-labels';

export default function ShiftCreatePage() {
    const { t } = useTranslation(['shifts', 'common']);
    const [saving, setSaving] = useState(false);
    const [name, setName] = useState('');
    const [code, setCode] = useState('');
    const [startTime, setStartTime] = useState('08:00');
    const [endTime, setEndTime] = useState('17:00');
    const [breakMinutes, setBreakMinutes] = useState('60');
    const [kind, setKind] = useState('standard');
    const [isNight, setIsNight] = useState(false);
    const [isFlexible, setIsFlexible] = useState(false);

    async function handleSubmit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const shift = await shiftApi.createShift({
                name,
                code,
                start_time: startTime,
                end_time: endTime,
                break_minutes: Number(breakMinutes) || 0,
                kind,
                is_night: isNight,
                is_flexible: isFlexible,
            });
            toast.success(t('create.toast_success'));
            router.visit(`/shifts/${shift.id}`);
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('create.toast_error'),
            );
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={t('create.title')}
            description={t('create.description')}
            permission="can_manage_shift_definitions"
        >
            <form onSubmit={handleSubmit} className="grid max-w-xl gap-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="code">
                            {t('code', { ns: 'common' })}
                        </Label>
                        <Input
                            id="code"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            required
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="name">
                            {t('name', { ns: 'common' })}
                        </Label>
                        <Input
                            id="name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            required
                        />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="start_time">
                            {t('create.start_time')}
                        </Label>
                        <TimePicker
                            id="start_time"
                            value={startTime}
                            onChange={setStartTime}
                            required
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="end_time">{t('create.end_time')}</Label>
                        <TimePicker
                            id="end_time"
                            value={endTime}
                            onChange={setEndTime}
                            required
                        />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="break_minutes">
                            {t('create.break_minutes')}
                        </Label>
                        <Input
                            id="break_minutes"
                            type="number"
                            min={0}
                            value={breakMinutes}
                            onChange={(e) => setBreakMinutes(e.target.value)}
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="kind">{t('create.kind')}</Label>
                        <select
                            id="kind"
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={kind}
                            onChange={(e) => {
                                const next = e.target.value;
                                setKind(next);
                                setIsNight(next === 'night');
                                setIsFlexible(next === 'flexible');
                            }}
                        >
                            {SHIFT_KINDS.map((value) => (
                                <option key={value} value={value}>
                                    {shiftKindLabel(t, value)}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex gap-4 text-sm">
                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={isNight}
                            onChange={(e) => setIsNight(e.target.checked)}
                        />
                        {t('create.is_night')}
                    </label>
                    <label className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={isFlexible}
                            onChange={(e) => setIsFlexible(e.target.checked)}
                        />
                        {t('create.is_flexible')}
                    </label>
                </div>
                <div>
                    <Button type="submit" disabled={saving}>
                        {saving
                            ? t('create.saving')
                            : t('create', { ns: 'common' })}
                    </Button>
                </div>
            </form>
        </AdminPageShell>
    );
}
