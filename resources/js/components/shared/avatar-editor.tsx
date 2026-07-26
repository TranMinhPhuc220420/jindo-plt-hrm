import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { ApiError } from '@/lib/api/errors';

type Props = {
    name: string;
    avatarUrl: string | null | undefined;
    disabled?: boolean;
    onUpload: (file: File) => Promise<void>;
    onRemove: () => Promise<void>;
};

export function AvatarEditor({
    name,
    avatarUrl,
    disabled = false,
    onUpload,
    onRemove,
}: Props) {
    const { t } = useTranslation(['common']);
    const getInitials = useInitials();
    const inputRef = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    const displayUrl = previewUrl ?? avatarUrl ?? undefined;

    async function handleFileChange(file: File | undefined) {
        if (!file || disabled) {
            return;
        }

        setBusy(true);
        const localPreview = URL.createObjectURL(file);
        setPreviewUrl(localPreview);

        try {
            await onUpload(file);
            setPreviewUrl(null);
            toast.success(t('avatar.toast_updated'));
        } catch (err) {
            setPreviewUrl(null);
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('avatar.toast_failed'),
            );
        } finally {
            URL.revokeObjectURL(localPreview);
            setBusy(false);
            if (inputRef.current) {
                inputRef.current.value = '';
            }
        }
    }

    async function handleRemove() {
        if (disabled || !avatarUrl) {
            return;
        }

        setBusy(true);

        try {
            await onRemove();
            setPreviewUrl(null);
            toast.success(t('avatar.toast_removed'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('avatar.toast_failed'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="flex items-center gap-4">
            <Avatar className="size-16 overflow-hidden rounded-full">
                <AvatarImage src={displayUrl} alt={name} />
                <AvatarFallback className="rounded-full bg-neutral-200 text-base text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(name)}
                </AvatarFallback>
            </Avatar>

            <div className="flex flex-wrap gap-2">
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="hidden"
                    disabled={disabled || busy}
                    onChange={(e) =>
                        void handleFileChange(e.target.files?.[0])
                    }
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={disabled || busy}
                    onClick={() => inputRef.current?.click()}
                >
                    {t('avatar.upload')}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled={disabled || busy || !avatarUrl}
                    onClick={() => void handleRemove()}
                >
                    {t('avatar.remove')}
                </Button>
            </div>
        </div>
    );
}
