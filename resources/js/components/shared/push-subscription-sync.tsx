import { useEffect } from 'react';
import { useAuth } from '@/lib/auth/auth-context';
import { syncGrantedPushSubscription } from '@/lib/push/web-push';

/**
 * Keep an existing granted Web Push subscription registered after login.
 */
export function PushSubscriptionSync() {
    const { user, vapidPublicKey } = useAuth();

    useEffect(() => {
        if (!user) {
            return;
        }

        void syncGrantedPushSubscription(vapidPublicKey);
    }, [user, vapidPublicKey]);

    return null;
}
