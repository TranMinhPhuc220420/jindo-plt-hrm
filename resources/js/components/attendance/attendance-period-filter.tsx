import {
    endOfMonth,
    endOfWeek,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import { useTranslation } from 'react-i18next';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { displayDate, formatDateString } from '@/lib/datetime';

export type AttendancePeriodPreset = 'today' | 'week' | 'month' | 'custom';

type Range = { from: string; to: string };

type Props = {
    preset: AttendancePeriodPreset;
    dateFrom: string;
    dateTo: string;
    onPresetChange: (preset: AttendancePeriodPreset, range: Range) => void;
    onCustomRangeChange: (range: Range) => void;
};

/** Company default week start is Monday (SettingsDefaults). */
const WEEK_STARTS_ON = 1 as const;

export function rangeForPreset(
    preset: Exclude<AttendancePeriodPreset, 'custom'>,
    now = new Date(),
): Range {
    if (preset === 'today') {
        const day = formatDateString(now);

        return { from: day, to: day };
    }

    if (preset === 'week') {
        return {
            from: formatDateString(
                startOfWeek(now, { weekStartsOn: WEEK_STARTS_ON }),
            ),
            to: formatDateString(
                endOfWeek(now, { weekStartsOn: WEEK_STARTS_ON }),
            ),
        };
    }

    return {
        from: formatDateString(startOfMonth(now)),
        to: formatDateString(endOfMonth(now)),
    };
}

export function AttendancePeriodFilter({
    preset,
    dateFrom,
    dateTo,
    onPresetChange,
    onCustomRangeChange,
}: Props) {
    const { t, i18n } = useTranslation(['attendance', 'common']);

    const periodLabel =
        dateFrom === dateTo
            ? displayDate(dateFrom, i18n.language)
            : `${displayDate(dateFrom, i18n.language)} – ${displayDate(dateTo, i18n.language)}`;

    return (
        <div className="mb-4 flex flex-col gap-3">
            <div className="space-y-1.5">
                <Label id="attendance_period_label">
                    {t('index.period_filter')}
                </Label>
                <ToggleGroup
                    type="single"
                    value={preset}
                    onValueChange={(value) => {
                        if (
                            value !== 'today' &&
                            value !== 'week' &&
                            value !== 'month' &&
                            value !== 'custom'
                        ) {
                            return;
                        }

                        if (value === 'custom') {
                            onPresetChange(value, {
                                from: dateFrom,
                                to: dateTo,
                            });

                            return;
                        }

                        onPresetChange(value, rangeForPreset(value));
                    }}
                    variant="outline"
                    size="sm"
                    className="flex w-full flex-wrap justify-start sm:w-fit"
                    aria-labelledby="attendance_period_label"
                >
                    <ToggleGroupItem value="today" className="px-3">
                        {t('index.filter_today')}
                    </ToggleGroupItem>
                    <ToggleGroupItem value="week" className="px-3">
                        {t('index.filter_week')}
                    </ToggleGroupItem>
                    <ToggleGroupItem value="month" className="px-3">
                        {t('index.filter_month')}
                    </ToggleGroupItem>
                    <ToggleGroupItem value="custom" className="px-3">
                        {t('index.filter_custom')}
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>

            {preset === 'custom' ? (
                <div className="space-y-1">
                    <Label htmlFor="attendance_range">
                        {t('index.date_range')}
                    </Label>
                    <DateRangePicker
                        id="attendance_range"
                        from={dateFrom}
                        to={dateTo}
                        onChange={onCustomRangeChange}
                        numberOfMonths={1}
                        className="min-w-[16rem]"
                    />
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">{periodLabel}</p>
            )}
        </div>
    );
}
