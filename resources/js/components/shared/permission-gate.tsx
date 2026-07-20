import type { ReactNode } from 'react';
import { useAuth } from '@/lib/auth/auth-context';

type Props = {
    permission?: string;
    any?: string[];
    all?: string[];
    fallback?: ReactNode;
    children: ReactNode;
};

/**
 * UI visibility helper. Server APIs remain the real authorization boundary.
 */
export function PermissionGate({
    permission,
    any,
    all,
    fallback = null,
    children,
}: Props) {
    const { can, canAny, canAll } = useAuth();

    let allowed = true;

    if (permission) {
        allowed = can(permission);
    } else if (any && any.length > 0) {
        allowed = canAny(any);
    } else if (all && all.length > 0) {
        allowed = canAll(all);
    }

    if (!allowed) {
        return fallback;
    }

    return children;
}
