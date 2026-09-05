import * as notificationsApi from '@/lib/api/modules/notifications';

const ENDPOINT_KEY = 'hrm.push.endpoint';
const DISMISS_KEY = 'hrm.push-reminder.dismissed';

export function isPushSupported(): boolean {
    return (
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window
    );
}

export function isReminderPromptDismissed(): boolean {
    try {
        return window.localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

export function dismissReminderPrompt(): void {
    try {
        window.localStorage.setItem(DISMISS_KEY, '1');
    } catch {
        // Ignore quota / private mode.
    }
}

export async function registerPushServiceWorker(): Promise<ServiceWorkerRegistration | null> {
    if (!isPushSupported()) {
        return null;
    }

    try {
        return await navigator.serviceWorker.register('/sw.js');
    } catch {
        return null;
    }
}

function urlBase64ToUint8Array(base64String: string): BufferSource {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

function storeEndpoint(endpoint: string | null): void {
    try {
        if (endpoint) {
            window.localStorage.setItem(ENDPOINT_KEY, endpoint);
        } else {
            window.localStorage.removeItem(ENDPOINT_KEY);
        }
    } catch {
        // Ignore.
    }
}

export async function subscribeToPush(
    vapidPublicKey: string,
): Promise<boolean> {
    if (!isPushSupported() || vapidPublicKey === '') {
        return false;
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return false;
    }

    const registration = await registerPushServiceWorker();

    if (!registration) {
        return false;
    }

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const json = subscription.toJSON();
    const endpoint = json.endpoint;
    const p256dh = json.keys?.p256dh;
    const auth = json.keys?.auth;

    if (!endpoint || !p256dh || !auth) {
        return false;
    }

    await notificationsApi.savePushSubscription({
        endpoint,
        keys: { p256dh, auth },
        content_encoding: 'aes128gcm',
    });
    await notificationsApi.updatePreferences({ push: true });
    storeEndpoint(endpoint);

    return true;
}

export async function unsubscribeFromPush(
    options: { updatePreference?: boolean } = {},
): Promise<void> {
    const updatePreference = options.updatePreference ?? true;
    let endpoint: string | null = null;

    try {
        endpoint = window.localStorage.getItem(ENDPOINT_KEY);
    } catch {
        endpoint = null;
    }

    if (isPushSupported()) {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription =
                await registration.pushManager.getSubscription();
            endpoint = subscription?.endpoint ?? endpoint;

            if (subscription) {
                await subscription.unsubscribe();
            }
        } catch {
            // Continue with stored endpoint.
        }
    }

    if (endpoint) {
        try {
            await notificationsApi.deletePushSubscription(endpoint);
        } catch {
            // Best-effort; token may already be gone.
        }
    }

    storeEndpoint(null);

    if (!updatePreference) {
        return;
    }

    try {
        await notificationsApi.updatePreferences({ push: false });
    } catch {
        // Guest / already logged out.
    }
}

export async function syncGrantedPushSubscription(
    vapidPublicKey: string | null,
): Promise<void> {
    if (
        !isPushSupported() ||
        !vapidPublicKey ||
        Notification.permission !== 'granted'
    ) {
        return;
    }

    const registration = await registerPushServiceWorker();

    if (!registration) {
        return;
    }

    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
    }

    const json = subscription.toJSON();
    const endpoint = json.endpoint;
    const p256dh = json.keys?.p256dh;
    const auth = json.keys?.auth;

    if (!endpoint || !p256dh || !auth) {
        return;
    }

    await notificationsApi.savePushSubscription({
        endpoint,
        keys: { p256dh, auth },
        content_encoding: 'aes128gcm',
    });
    storeEndpoint(endpoint);
}
