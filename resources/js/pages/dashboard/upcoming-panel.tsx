import { format, parseISO } from 'date-fns';
import { useTranslation } from 'react-i18next';
import type { UpcomingItem } from '@/lib/api/modules/dashboard';
import { dateFnsLocale } from '@/lib/datetime';

type Props = {
    items: UpcomingItem[];
};

export function UpcomingPanel({ items }: Props) {
    const { t, i18n } = useTranslation('dashboard');
    const locale = dateFnsLocale(i18n.language);

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <h2 className="mb-4 text-lg font-semibold">{t('upcoming_title')}</h2>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('upcoming_empty')}
                </p>
            ) : (
                <ul className="space-y-3">
                    {items.map((item, index) => {
                        let month = '';
                        let day = '';

                        try {
                            const d = parseISO(item.date);
                            month = format(d, 'MMM', { locale });
                            day = format(d, 'd');
                        } catch {
                            day = item.date;
                        }

                        return (
                            <li
                                key={`${item.kind}-${item.date}-${index}`}
                                className="flex items-center gap-3"
                            >
                                <div className="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-muted text-primary">
                                    <span className="text-[10px] font-bold uppercase leading-none">
                                        {month}
                                    </span>
                                    <span className="text-lg font-bold leading-tight">
                                        {day}
                                    </span>
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {item.title}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {item.kind === 'holiday'
                                            ? t('upcoming_holiday')
                                            : t('upcoming_leave')}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
