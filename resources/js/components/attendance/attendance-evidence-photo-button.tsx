import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ApiError } from '@/lib/api/errors';
import * as attendanceApi from '@/lib/api/modules/attendance';
import type { AttendanceEvidence } from '@/lib/api/modules/attendance';

type Props = {
    recordId: number;
    evidence: AttendanceEvidence;
    className?: string;
};

export function AttendanceEvidencePhotoButton({
    recordId,
    evidence,
    className,
}: Props) {
    const { t } = useTranslation('attendance');
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [objectUrl, setObjectUrl] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [objectUrl]);

    async function handleOpen() {
        if (!evidence.has_photo) {
            return;
        }

        setLoading(true);

        try {
            const url = await attendanceApi.fetchEvidencePhotoObjectUrl(
                recordId,
                evidence.punch_type,
            );
            setObjectUrl((prev) => {
                if (prev) {
                    URL.revokeObjectURL(prev);
                }

                return url;
            });
            setOpen(true);
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('evidence.photo_load_error'),
            );
        } finally {
            setLoading(false);
        }
    }

    if (!evidence.has_photo) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                className={
                    className ??
                    'text-primary underline-offset-2 hover:underline'
                }
                disabled={loading}
                onClick={() => void handleOpen()}
            >
                {loading
                    ? t('evidence.photo_loading')
                    : t('evidence.view_photo')}
            </button>

            <Dialog
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (!next) {
                        setObjectUrl((prev) => {
                            if (prev) {
                                URL.revokeObjectURL(prev);
                            }

                            return null;
                        });
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {evidence.punch_type === 'check_in'
                                ? t('evidence.check_in_label')
                                : t('evidence.check_out_label')}
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {evidence.address}
                    </p>
                    {objectUrl ? (
                        <img
                            src={objectUrl}
                            alt={t('evidence.photo_preview_alt')}
                            className="max-h-[70vh] w-full rounded-md border object-contain"
                        />
                    ) : null}
                    <div className="flex justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            {t('evidence.cancel')}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
