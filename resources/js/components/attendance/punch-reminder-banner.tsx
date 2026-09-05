import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/lib/auth/auth-context';
import {
    dismissReminderPrompt,
    isPushSupported,
    isReminderPromptDismissed,
    subscribeToPush,
} from '@/lib/push/web-push';

export function PunchReminderBanner() {
    const { t } = useTranslation('attendance');
    const { vapidPublicKey, user } = useAuth();
    const [hidden, setHidden] = useState(() => isReminderPromptDismissed());
    const [busy, setBusy] = useState(false);

    if (
        hidden ||
        !user ||
        !vapidPublicKey ||
        !isPushSupported() ||
        typeof Notification === 'undefined' ||
        Notification.permission !== 'default'
    ) {
        return null;
    }

    async function enable() {
        if (!vapidPublicKey) {
            return;
        }

        setBusy(true);

        try {
            const ok = await subscribeToPush(vapidPublicKey);

            if (ok) {
                toast.success(t('index.punch_reminder_enabled'));
                dismissReminderPrompt();
                setHidden(true);
            } else {
                toast.error(t('index.punch_reminder_denied'));
                dismissReminderPrompt();
                setHidden(true);
            }
        } catch {
            toast.error(t('index.punch_reminder_error'));
        } finally {
            setBusy(false);
        }
    }

    function later() {
        dismissReminderPrompt();
        setHidden(true);
    }

    return (
        <div className="mb-4 flex flex-col gap-3 rounded-lg border border-border bg-muted/40 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="font-medium">{t('index.punch_reminder_title')}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('index.punch_reminder_body')}
                </p>
            </div>
            <div className="flex shrink-0 gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    className="min-h-11 sm:min-h-0"
                    disabled={busy}
                    onClick={later}
                >
                    {t('index.punch_reminder_later')}
                </Button>
                <Button
                    size="sm"
                    className="min-h-11 sm:min-h-0"
                    disabled={busy}
                    onClick={() => void enable()}
                >
                    {t('index.punch_reminder_enable')}
                </Button>
            </div>
        </div>
    );
}
