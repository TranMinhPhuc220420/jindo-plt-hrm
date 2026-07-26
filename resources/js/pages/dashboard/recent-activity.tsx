import { Link } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { useTranslation } from 'react-i18next';
import type { ActivityItem } from '@/lib/api/modules/dashboard';
import { dateFnsLocale } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    items: ActivityItem[];
};

export function RecentActivity({ items }: Props) {
    const { t, i18n } = useTranslation('dashboard');
    const locale = dateFnsLocale(i18n.language);

    return (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-2">
                <h2 className="text-lg font-semibold">
                    {t('recent_activity_title')}
                </h2>
                <Link
                    href="/notifications"
                    className="text-sm font-medium text-primary hover:underline"
                >
                    {t('view_notifications')}
                </Link>
            </div>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('recent_activity_empty')}
                </p>
            ) : (
                <ul className="relative space-y-4 before:absolute before:top-2 before:bottom-2 before:left-[15px] before:w-0.5 before:bg-border">
                    {items.map((item) => {
                        const created = item.created_at
                            ? new Date(item.created_at)
                            : null;
                        const relative =
                            created && !Number.isNaN(created.getTime())
                                ? formatDistanceToNow(created, {
                                      addSuffix: true,
                                      locale,
                                  })
                                : null;
                        const unread = !item.read_at;

                        return (
                            <li key={item.id} className="relative pl-10">
                                <span
                                    className={cn(
                                        'absolute top-1 left-1.5 size-6 rounded-full border-4 border-card',
                                        unread
                                            ? 'bg-primary'
                                            : 'bg-muted-foreground/40',
                                    )}
                                />
                                <p className="text-sm leading-snug font-medium">
                                    {item.title}
                                </p>
                                {item.body ? (
                                    <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                                        {item.body}
                                    </p>
                                ) : null}
                                {relative ? (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {relative}
                                    </p>
                                ) : null}
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
