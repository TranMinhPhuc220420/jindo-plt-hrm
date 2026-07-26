import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    getCurrentPunchLocation,
    mapGeolocationError,
    type PunchLocation,
} from '@/lib/attendance/geolocation';

export type PunchEvidencePayload = {
    latitude: number;
    longitude: number;
    accuracy_meters: number | null;
    address: string;
    photo: File;
    captured_at: string;
};

type Props = {
    open: boolean;
    mode: 'check_in' | 'check_out';
    busy: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (payload: PunchEvidencePayload) => void | Promise<void>;
};

export function PunchEvidenceDialog({
    open,
    mode,
    busy,
    onOpenChange,
    onSubmit,
}: Props) {
    const { t } = useTranslation('attendance');
    const videoRef = useRef<HTMLVideoElement | null>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [location, setLocation] = useState<PunchLocation | null>(null);
    const [locationError, setLocationError] = useState<string | null>(null);
    const [locationLoading, setLocationLoading] = useState(false);
    const [cameraError, setCameraError] = useState<string | null>(null);
    const [photo, setPhoto] = useState<File | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const abort = new AbortController();
        let cancelled = false;

        async function boot() {
            setLocation(null);
            setLocationError(null);
            setCameraError(null);
            setPhoto(null);
            setPreviewUrl((prev) => {
                if (prev) {
                    URL.revokeObjectURL(prev);
                }

                return null;
            });
            setLocationLoading(true);

            try {
                const loc = await getCurrentPunchLocation(abort.signal);
                if (!cancelled) {
                    setLocation(loc);
                }
            } catch (error) {
                if (!cancelled && !(error instanceof DOMException && error.name === 'AbortError')) {
                    setLocationError(mapGeolocationError(error));
                }
            } finally {
                if (!cancelled) {
                    setLocationLoading(false);
                }
            }

            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new Error('CAMERA_UNSUPPORTED');
                }

                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                });

                if (cancelled) {
                    stream.getTracks().forEach((track) => track.stop());

                    return;
                }

                streamRef.current = stream;
                if (videoRef.current) {
                    videoRef.current.srcObject = stream;
                    await videoRef.current.play().catch(() => undefined);
                }
            } catch {
                if (!cancelled) {
                    setCameraError('camera_denied');
                }
            }
        }

        void boot();

        return () => {
            cancelled = true;
            abort.abort();
            streamRef.current?.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        };
    }, [open]);

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    function capturePhoto() {
        const video = videoRef.current;

        if (!video || video.videoWidth === 0) {
            setCameraError('camera_not_ready');

            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');

        if (!ctx) {
            setCameraError('camera_not_ready');

            return;
        }

        ctx.drawImage(video, 0, 0);
        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    setCameraError('camera_not_ready');

                    return;
                }

                const file = new File([blob], `attendance-${mode}.jpg`, {
                    type: 'image/jpeg',
                });
                setPhoto(file);
                setPreviewUrl((prev) => {
                    if (prev) {
                        URL.revokeObjectURL(prev);
                    }

                    return URL.createObjectURL(blob);
                });
                setCameraError(null);
            },
            'image/jpeg',
            0.9,
        );
    }

    function retake() {
        setPhoto(null);
        setPreviewUrl((prev) => {
            if (prev) {
                URL.revokeObjectURL(prev);
            }

            return null;
        });
    }

    async function handleSubmit() {
        if (!location || !photo) {
            return;
        }

        setSubmitting(true);

        try {
            await onSubmit({
                latitude: location.latitude,
                longitude: location.longitude,
                accuracy_meters: location.accuracyMeters,
                address: location.address,
                photo,
                captured_at: new Date().toISOString(),
            });
        } finally {
            setSubmitting(false);
        }
    }

    const canSubmit = !!location && !!photo && !busy && !submitting;
    const titleKey =
        mode === 'check_in' ? 'evidence.title_in' : 'evidence.title_out';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[min(92vh,820px)] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t(titleKey)}</DialogTitle>
                    <DialogDescription>
                        {t('evidence.description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <section className="space-y-2">
                        <h3 className="text-sm font-medium">
                            {t('evidence.location')}
                        </h3>
                        {locationLoading ? (
                            <p className="text-sm text-muted-foreground">
                                {t('evidence.location_loading')}
                            </p>
                        ) : locationError ? (
                            <p className="text-sm text-destructive">
                                {t(`evidence.${locationError}`)}
                            </p>
                        ) : location ? (
                            <div className="rounded-md border bg-muted/30 p-3 text-sm">
                                <p>{location.address}</p>
                                <p className="mt-1 text-xs text-muted-foreground tabular-nums">
                                    {location.latitude.toFixed(6)},{' '}
                                    {location.longitude.toFixed(6)}
                                    {location.accuracyMeters != null
                                        ? ` (±${Math.round(location.accuracyMeters)}m)`
                                        : null}
                                </p>
                            </div>
                        ) : null}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={locationLoading || busy || submitting}
                            onClick={() => {
                                setLocationLoading(true);
                                setLocationError(null);
                                void getCurrentPunchLocation()
                                    .then(setLocation)
                                    .catch((error) => {
                                        setLocation(null);
                                        setLocationError(
                                            mapGeolocationError(error),
                                        );
                                    })
                                    .finally(() => setLocationLoading(false));
                            }}
                        >
                            {t('evidence.retry_location')}
                        </Button>
                    </section>

                    <section className="space-y-2">
                        <h3 className="text-sm font-medium">
                            {t('evidence.photo')}
                        </h3>
                        {cameraError ? (
                            <p className="text-sm text-destructive">
                                {t(`evidence.${cameraError}`)}
                            </p>
                        ) : null}
                        {previewUrl ? (
                            <img
                                src={previewUrl}
                                alt={t('evidence.photo_preview_alt')}
                                className="aspect-video w-full rounded-md border object-cover"
                            />
                        ) : (
                            <video
                                ref={videoRef}
                                muted
                                playsInline
                                className="aspect-video w-full rounded-md border bg-black object-cover"
                            />
                        )}
                        <div className="flex flex-wrap gap-2">
                            {photo ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={busy || submitting}
                                    onClick={retake}
                                >
                                    {t('evidence.retake')}
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    size="sm"
                                    disabled={
                                        !!cameraError || busy || submitting
                                    }
                                    onClick={capturePhoto}
                                >
                                    {t('evidence.capture')}
                                </Button>
                            )}
                        </div>
                    </section>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={busy || submitting}
                        onClick={() => onOpenChange(false)}
                    >
                        {t('evidence.cancel')}
                    </Button>
                    <Button
                        type="button"
                        disabled={!canSubmit}
                        onClick={() => void handleSubmit()}
                    >
                        {busy || submitting
                            ? t('evidence.submitting')
                            : t('evidence.confirm')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
