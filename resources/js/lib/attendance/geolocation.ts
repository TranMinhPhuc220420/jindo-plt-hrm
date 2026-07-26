export type PunchLocation = {
    latitude: number;
    longitude: number;
    accuracyMeters: number | null;
    address: string;
};

function formatCoordFallback(latitude: number, longitude: number): string {
    return `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
}

export async function getCurrentPunchLocation(
    signal?: AbortSignal,
): Promise<PunchLocation> {
    if (!('geolocation' in navigator)) {
        throw new Error('GEOLOCATION_UNSUPPORTED');
    }

    const position = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 15_000,
            maximumAge: 0,
        });
    });

    if (signal?.aborted) {
        throw new DOMException('Aborted', 'AbortError');
    }

    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const accuracyMeters =
        typeof position.coords.accuracy === 'number'
            ? position.coords.accuracy
            : null;

    const address = await reverseGeocode(latitude, longitude, signal);

    return {
        latitude,
        longitude,
        accuracyMeters,
        address,
    };
}

async function reverseGeocode(
    latitude: number,
    longitude: number,
    signal?: AbortSignal,
): Promise<string> {
    const fallback = formatCoordFallback(latitude, longitude);
    const url = new URL('https://nominatim.openstreetmap.org/reverse');
    url.searchParams.set('format', 'jsonv2');
    url.searchParams.set('lat', String(latitude));
    url.searchParams.set('lon', String(longitude));
    url.searchParams.set('zoom', '18');
    url.searchParams.set('addressdetails', '0');

    try {
        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), 8_000);
        const onAbort = () => controller.abort();
        signal?.addEventListener('abort', onAbort);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                return fallback;
            }

            const body = (await response.json()) as { display_name?: string };
            const name = body.display_name?.trim();

            if (name && name.length > 0) {
                return name.slice(0, 500);
            }
        } finally {
            window.clearTimeout(timer);
            signal?.removeEventListener('abort', onAbort);
        }
    } catch {
        // Fall through to coordinate string.
    }

    return fallback;
}

export function mapGeolocationError(error: unknown): string {
    if (error instanceof GeolocationPositionError) {
        if (error.code === error.PERMISSION_DENIED) {
            return 'gps_denied';
        }

        if (error.code === error.TIMEOUT) {
            return 'gps_timeout';
        }

        return 'gps_unavailable';
    }

    if (error instanceof Error && error.message === 'GEOLOCATION_UNSUPPORTED') {
        return 'gps_unsupported';
    }

    return 'gps_unavailable';
}
