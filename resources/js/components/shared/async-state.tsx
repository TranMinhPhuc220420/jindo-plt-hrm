import { useTranslation } from 'react-i18next';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    label?: string;
};

export function LoadingState({ label }: Props) {
    const { t } = useTranslation('common');

    return (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Spinner />
            <span>{label ?? t('loading')}</span>
        </div>
    );
}

export function EmptyState({ message }: { message: string }) {
    return (
        <p className="rounded-lg border border-dashed border-border bg-muted/40 px-4 py-8 text-center text-sm text-muted-foreground">
            {message}
        </p>
    );
}

export function ErrorState({ message }: { message: string }) {
    return (
        <p className="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
            {message}
        </p>
    );
}
