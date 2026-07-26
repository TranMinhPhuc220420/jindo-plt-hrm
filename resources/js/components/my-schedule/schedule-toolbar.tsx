import { CalendarDays, ChevronLeft, ChevronRight, List } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { ScheduleViewMode } from './schedule-view';

type Props = {
    view: ScheduleViewMode;
    onViewChange: (view: ScheduleViewMode) => void;
    dateFrom: string;
    dateTo: string;
    onRangeChange: (range: { from: string; to: string }) => void;
    monthLabel: string;
    onPrevMonth: () => void;
    onNextMonth: () => void;
    onGoToday: () => void;
    onSearch: () => void;
};

export function ScheduleToolbar({
    view,
    onViewChange,
    dateFrom,
    dateTo,
    onRangeChange,
    monthLabel,
    onPrevMonth,
    onNextMonth,
    onGoToday,
    onSearch,
}: Props) {
    const { t } = useTranslation(['shifts', 'common']);

    return (
        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <ToggleGroup
                type="single"
                value={view}
                onValueChange={(value) => {
                    if (value === 'calendar' || value === 'table') {
                        onViewChange(value);
                    }
                }}
                variant="outline"
                size="sm"
                className="w-fit"
                aria-label={t('my_schedule.title')}
            >
                <ToggleGroupItem
                    value="calendar"
                    aria-label={t('my_schedule.view_calendar')}
                    className="min-h-9 min-w-9"
                >
                    <CalendarDays className="size-4" />
                    <span className="hidden sm:inline">
                        {t('my_schedule.view_calendar')}
                    </span>
                </ToggleGroupItem>
                <ToggleGroupItem
                    value="table"
                    aria-label={t('my_schedule.view_table')}
                    className="min-h-9 min-w-9"
                >
                    <List className="size-4" />
                    <span className="hidden sm:inline">
                        {t('my_schedule.view_table')}
                    </span>
                </ToggleGroupItem>
            </ToggleGroup>

            {view === 'calendar' ? (
                <div className="flex w-full flex-wrap items-center justify-between gap-2 sm:w-auto sm:justify-start">
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-9"
                            onClick={onPrevMonth}
                            aria-label={t('my_schedule.prev_month')}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <p className="min-w-[8rem] text-center text-sm font-medium capitalize sm:min-w-[10rem]">
                            {monthLabel}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-9"
                            onClick={onNextMonth}
                            aria-label={t('my_schedule.next_month')}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        className="min-h-9"
                        onClick={onGoToday}
                    >
                        {t('my_schedule.today')}
                    </Button>
                </div>
            ) : (
                <form
                    className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-end"
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSearch();
                    }}
                >
                    <div className="w-full space-y-1 sm:w-auto">
                        <Label htmlFor="schedule_range">
                            {t('my_schedule.date_from')}
                            {' – '}
                            {t('my_schedule.date_to')}
                        </Label>
                        <DateRangePicker
                            id="schedule_range"
                            from={dateFrom}
                            to={dateTo}
                            onChange={onRangeChange}
                            numberOfMonths={1}
                            className="w-full min-w-0 sm:min-w-[16rem]"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="secondary"
                        className="min-h-9 w-full sm:w-auto"
                    >
                        {t('search', { ns: 'common' })}
                    </Button>
                </form>
            )}
        </div>
    );
}
