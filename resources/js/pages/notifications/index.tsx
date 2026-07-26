import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { Button } from '@/components/ui/button';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { useIsMobile } from '@/hooks/use-mobile';
import { ApiError } from '@/lib/api/errors';
import * as notificationsApi from '@/lib/api/modules/notifications';
import type {
    Notification,
    NotificationPreferences,
} from '@/lib/api/modules/notifications';
import {
    notificationBody,
    notificationTitle,
    notificationTypeLabel,
} from '@/lib/i18n/notification-labels';
import { notificationHref } from '@/lib/notifications/notification-href';
import { cn } from '@/lib/utils';

export default function NotificationsPage() {
    const { t, i18n } = useTranslation(['notifications', 'common']);
    const isMobile = useIsMobile();
    const [items, setItems] = useState<Notification[]>([]);
    const [unread, setUnread] = useState(0);
    const [prefs, setPrefs] = useState<NotificationPreferences | null>(null);
    const [unreadOnly, setUnreadOnly] = useState(false);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [list, count, preferences] = await Promise.all([
                notificationsApi.listNotifications({
                    per_page: 30,
                    unread_only: unreadOnly,
                }),
                notificationsApi.unreadCount(),
                notificationsApi.getPreferences(),
            ]);
            setItems(list.data);
            setUnread(count);
            setPrefs(preferences);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('error_load'));
        } finally {
            setLoading(false);
        }
    }, [t, unreadOnly]);

    useLoadEffect(load, [load]);

    async function handleMarkRead(id: number) {
        try {
            await notificationsApi.markRead(id);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        }
    }

    async function handleMarkAll() {
        setBusy(true);

        try {
            await notificationsApi.markAllRead();
            toast.success(t('toast_all_read'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDelete(id: number) {
        try {
            await notificationsApi.deleteNotification(id);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        }
    }

    async function handleTogglePref(key: 'email' | 'push' | 'system') {
        if (!prefs) {
            return;
        }

        const next = { ...prefs, [key]: !prefs[key] };
        setPrefs(next);

        try {
            const saved = await notificationsApi.updatePreferences({
                email: next.email,
                push: next.push,
                system: next.system,
            });
            setPrefs(saved);
            toast.success(t('toast_prefs_saved'));
        } catch (err) {
            setPrefs(prefs);
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        }
    }

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            permission="can_view_own_notifications"
        >
            <div
                className={cn(
                    'mb-4 flex gap-3',
                    isMobile ? 'flex-col' : 'flex-wrap items-center',
                )}
            >
                <span className="w-fit rounded-full bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary">
                    {t('unread_badge', { count: unread })}
                </span>
                <div
                    className={cn(
                        'flex gap-2',
                        isMobile ? 'w-full flex-col' : 'flex-wrap',
                    )}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        className={cn(isMobile && 'min-h-11 w-full')}
                        onClick={() => setUnreadOnly((v) => !v)}
                    >
                        {unreadOnly ? t('show_all') : t('show_unread')}
                    </Button>
                    <Button
                        size="sm"
                        className={cn(isMobile && 'min-h-11 w-full')}
                        disabled={busy || unread === 0}
                        onClick={() => void handleMarkAll()}
                    >
                        {t('mark_all_read')}
                    </Button>
                </div>
            </div>

            {prefs && (
                <div className="mb-6 grid w-full max-w-xl gap-3 rounded-lg border border-border p-4">
                    <h2 className="font-medium">{t('prefs_title')}</h2>
                    <div
                        className={cn(
                            'flex gap-4',
                            isMobile ? 'flex-col' : 'flex-wrap',
                        )}
                    >
                        {(['email', 'push', 'system'] as const).map((key) => (
                            <label
                                key={key}
                                className="flex min-h-11 items-center gap-2 text-sm sm:min-h-0"
                            >
                                <input
                                    type="checkbox"
                                    className="size-4"
                                    checked={prefs[key]}
                                    onChange={() => void handleTogglePref(key)}
                                />
                                {t(`prefs_${key}`)}
                            </label>
                        ))}
                    </div>
                </div>
            )}

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : items.length === 0 ? (
                <EmptyState message={t('empty')} />
            ) : (
                <ul className="space-y-2">
                    {items.map((item) => {
                        const title = notificationTitle(t, item);
                        const body = notificationBody(t, item);
                        const href = notificationHref(item);

                        return (
                            <li
                                key={item.id}
                                className={cn(
                                    'flex gap-3 rounded-lg border border-border p-3 text-sm',
                                    isMobile
                                        ? 'flex-col'
                                        : 'flex-wrap items-start justify-between',
                                    item.read_at ? 'opacity-70' : 'bg-muted/30',
                                )}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        {!item.read_at && (
                                            <span className="inline-block size-2 shrink-0 rounded-full bg-primary" />
                                        )}
                                        {href ? (
                                            <Link
                                                href={href}
                                                className="font-medium hover:underline"
                                                onClick={() => {
                                                    if (!item.read_at) {
                                                        void handleMarkRead(
                                                            item.id,
                                                        );
                                                    }
                                                }}
                                            >
                                                {title}
                                            </Link>
                                        ) : (
                                            <p className="font-medium">
                                                {title}
                                            </p>
                                        )}
                                    </div>
                                    {body && (
                                        <p className="mt-1 text-muted-foreground">
                                            {body}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {notificationTypeLabel(t, item.type)}
                                        {item.created_at
                                            ? ` · ${new Date(item.created_at).toLocaleString(i18n.language)}`
                                            : ''}
                                    </p>
                                </div>
                                <div
                                    className={cn(
                                        'flex shrink-0 gap-2',
                                        isMobile && 'w-full',
                                    )}
                                >
                                    {!item.read_at && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className={cn(
                                                isMobile && 'min-h-11 flex-1',
                                            )}
                                            onClick={() =>
                                                void handleMarkRead(item.id)
                                            }
                                        >
                                            {t('mark_read')}
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className={cn(
                                            isMobile && 'min-h-11 flex-1',
                                        )}
                                        onClick={() =>
                                            void handleDelete(item.id)
                                        }
                                    >
                                        {t('delete', { ns: 'common' })}
                                    </Button>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </AdminPageShell>
    );
}
