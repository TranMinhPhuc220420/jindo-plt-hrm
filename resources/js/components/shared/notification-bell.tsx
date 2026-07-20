import { Link, router } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Bell } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { ApiError } from '@/lib/api/errors';
import * as notificationsApi from '@/lib/api/modules/notifications';
import type { Notification } from '@/lib/api/modules/notifications';
import { useAuth } from '@/lib/auth/auth-context';
import { dateFnsLocale } from '@/lib/datetime';
import {
    notificationBody,
    notificationTitle,
} from '@/lib/i18n/notification-labels';
import { notificationHref } from '@/lib/notifications/notification-href';
import { cn } from '@/lib/utils';

const POLL_MS = 60_000;
const PANEL_PAGE_SIZE = 8;

function formatBadgeCount(count: number): string {
    if (count > 99) {
        return '99+';
    }

    return String(count);
}

export function NotificationBell() {
    const { t, i18n } = useTranslation(['notifications', 'common']);
    const { can, user, isLoading } = useAuth();
    const locale = dateFnsLocale(i18n.language);
    const [open, setOpen] = useState(false);
    const [unread, setUnread] = useState(0);
    const [items, setItems] = useState<Notification[]>([]);
    const [panelLoading, setPanelLoading] = useState(false);
    const [panelError, setPanelError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const refreshCount = useCallback(async () => {
        if (!user || !can('can_view_own_notifications')) {
            setUnread(0);

            return;
        }

        try {
            const count = await notificationsApi.unreadCount();
            setUnread(count);
        } catch {
            // Keep last known count; header must stay quiet on errors.
        }
    }, [user, can]);

    const loadPanel = useCallback(async () => {
        if (!user || !can('can_view_own_notifications')) {
            setItems([]);

            return;
        }

        setPanelLoading(true);
        setPanelError(null);

        try {
            const [list, count] = await Promise.all([
                notificationsApi.listNotifications({
                    per_page: PANEL_PAGE_SIZE,
                }),
                notificationsApi.unreadCount(),
            ]);
            setItems(list.data);
            setUnread(count);
        } catch (err) {
            setPanelError(
                err instanceof ApiError ? err.message : t('error_load'),
            );
        } finally {
            setPanelLoading(false);
        }
    }, [user, can, t]);

    useEffect(() => {
        if (isLoading) {
            return;
        }

        void refreshCount();

        const intervalId = window.setInterval(() => {
            void refreshCount();
        }, POLL_MS);

        const onFocus = () => {
            void refreshCount();
        };

        window.addEventListener('focus', onFocus);

        return () => {
            window.clearInterval(intervalId);
            window.removeEventListener('focus', onFocus);
        };
    }, [isLoading, refreshCount]);

    async function handleMarkRead(id: number) {
        try {
            await notificationsApi.markRead(id);
            setItems((prev) =>
                prev.map((item) =>
                    item.id === id
                        ? {
                              ...item,
                              read_at: item.read_at ?? new Date().toISOString(),
                          }
                        : item,
                ),
            );
            setUnread((n) => Math.max(0, n - 1));
        } catch {
            // Silent in panel; user can retry from full page.
        }
    }

    async function handleMarkAll() {
        setBusy(true);

        try {
            await notificationsApi.markAllRead();
            setItems((prev) =>
                prev.map((item) => ({
                    ...item,
                    read_at: item.read_at ?? new Date().toISOString(),
                })),
            );
            setUnread(0);
        } catch {
            // Silent in panel.
        } finally {
            setBusy(false);
        }
    }

    return (
        <PermissionGate permission="can_view_own_notifications">
            <DropdownMenu
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (next) {
                        void loadPanel();
                    }
                }}
            >
                <DropdownMenuTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="relative"
                        aria-label={t('header_bell_aria', { count: unread })}
                    >
                        <Bell className="size-5" />
                        {unread > 0 ? (
                            <span
                                className={cn(
                                    'absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full',
                                    'bg-destructive px-1 text-[10px] leading-none font-semibold text-white',
                                )}
                            >
                                {formatBadgeCount(unread)}
                            </span>
                        ) : null}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    sideOffset={8}
                    className="w-[min(100vw-2rem,22rem)] p-0"
                >
                    <div className="flex items-center justify-between gap-2 px-3 py-2.5">
                        <p className="text-sm font-semibold">
                            {t('panel_title')}
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-7 px-2 text-xs"
                            disabled={busy || unread === 0}
                            onClick={() => void handleMarkAll()}
                        >
                            {t('mark_all_read')}
                        </Button>
                    </div>
                    <Separator />
                    <div className="max-h-80 overflow-y-auto">
                        {panelLoading ? (
                            <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                                {t('loading')}
                            </p>
                        ) : panelError ? (
                            <p className="px-3 py-6 text-center text-sm text-destructive">
                                {panelError}
                            </p>
                        ) : items.length === 0 ? (
                            <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                                {t('empty')}
                            </p>
                        ) : (
                            <ul>
                                {items.map((item) => {
                                    const unreadItem = !item.read_at;
                                    const created = item.created_at
                                        ? new Date(item.created_at)
                                        : null;
                                    const relative =
                                        created &&
                                        !Number.isNaN(created.getTime())
                                            ? formatDistanceToNow(created, {
                                                  addSuffix: true,
                                                  locale,
                                              })
                                            : null;
                                    const title = notificationTitle(t, item);
                                    const body = notificationBody(t, item);
                                    const href = notificationHref(item);

                                    return (
                                        <li key={item.id}>
                                            <button
                                                type="button"
                                                className={cn(
                                                    'flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition-colors hover:bg-accent',
                                                    unreadItem && 'bg-muted/40',
                                                )}
                                                onClick={() => {
                                                    if (unreadItem) {
                                                        void handleMarkRead(
                                                            item.id,
                                                        );
                                                    }

                                                    if (href) {
                                                        setOpen(false);
                                                        router.visit(href);
                                                    }
                                                }}
                                            >
                                                <div className="flex items-start gap-2">
                                                    {unreadItem ? (
                                                        <span className="mt-1.5 size-2 shrink-0 rounded-full bg-destructive" />
                                                    ) : (
                                                        <span className="mt-1.5 size-2 shrink-0" />
                                                    )}
                                                    <div className="min-w-0 flex-1">
                                                        <p
                                                            className={cn(
                                                                'text-sm leading-snug',
                                                                unreadItem
                                                                    ? 'font-semibold'
                                                                    : 'font-medium text-muted-foreground',
                                                            )}
                                                        >
                                                            {title}
                                                        </p>
                                                        {body ? (
                                                            <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                                                                {body}
                                                            </p>
                                                        ) : null}
                                                        {relative ? (
                                                            <p className="mt-1 text-[11px] text-muted-foreground">
                                                                {relative}
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                </div>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                    <Separator />
                    <div className="p-1.5">
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-9 w-full justify-center text-sm font-medium"
                            asChild
                        >
                            <Link
                                href="/notifications"
                                onClick={() => setOpen(false)}
                            >
                                {t('see_earlier')}
                            </Link>
                        </Button>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </PermissionGate>
    );
}
