import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import type { PendingPunch } from '@/lib/attendance/punch-queue';

type Props = {
    pending: PendingPunch[];
    syncing: boolean;
    onSync: () => void;
};

export function PunchPendingSyncBanner({ pending, syncing, onSync }: Props) {
    const { t } = useTranslation('attendance');

    if (pending.length === 0) {
        return null;
    }

    const lastError = [...pending]
        .reverse()
        .find((row) => row.lastError)?.lastError;

    return (
        <div className="mb-4 flex flex-col gap-3 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-1 text-sm">
                <p className="font-medium">
                    {t('index.pending_sync_banner', { count: pending.length })}
                </p>
                {lastError ? (
                    <p className="text-muted-foreground">{lastError}</p>
                ) : null}
            </div>
            <Button
                type="button"
                size="sm"
                className="min-h-11 shrink-0 sm:min-h-9"
                disabled={syncing}
                onClick={onSync}
            >
                {syncing ? t('index.syncing') : t('index.sync_now')}
            </Button>
        </div>
    );
}
