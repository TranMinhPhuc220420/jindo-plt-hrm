import { router } from '@inertiajs/react';
import {
    startTransition,
    useCallback,
    useEffect,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { apiGet, setUnauthorizedHandler } from '@/lib/api/client';
import * as authApi from '@/lib/api/modules/auth';
import type { AuthPayload, AuthUser } from '@/lib/api/types';
import {
    AuthContext,
    canPermission,
    emptyAuthSession,
} from '@/lib/auth/auth-context';
import type { AuthContextValue, AuthSession } from '@/lib/auth/auth-context';
import { applyLocale, DEFAULT_LOCALE } from '@/lib/i18n';

type Props = {
    children: ReactNode;
};

type SessionPayload = {
    user: AuthUser;
    permissions: string[];
    employee_id?: number | null;
    two_factor_required?: boolean;
    locale?: string;
    user_locale?: string | null;
    company_locale?: string;
    vapid_public_key?: string | null;
};

/**
 * Auth lives outside Inertia's Page context (`withApp`), so this provider
 * must not call usePage(). Session source of truth is REST `/api/me`.
 */
export function AuthProvider({ children }: Props) {
    const [session, setSessionState] = useState<AuthSession>({
        ...emptyAuthSession,
        isLoading: true,
    });

    const clearSession = useCallback(() => {
        setSessionState((current) => {
            // Only reset language when ending an authenticated session.
            if (current.user !== null) {
                void applyLocale(DEFAULT_LOCALE);
            }

            return { ...emptyAuthSession, isLoading: false };
        });
    }, []);

    const setSession = useCallback((payload: SessionPayload) => {
        const locale = payload.locale ?? DEFAULT_LOCALE;

        setSessionState({
            user: payload.user,
            permissions: payload.permissions,
            employeeId: payload.employee_id ?? null,
            twoFactorRequired: payload.two_factor_required ?? false,
            locale,
            userLocale: payload.user_locale ?? null,
            companyLocale: payload.company_locale ?? null,
            vapidPublicKey: payload.vapid_public_key ?? null,
            isLoading: false,
        });

        void applyLocale(locale);
    }, []);

    const refreshMe = useCallback(async () => {
        setSessionState((current) => ({ ...current, isLoading: true }));

        try {
            const me = await authApi.getMe();
            setSession(me);
        } catch {
            clearSession();

            throw new Error('Failed to load /api/me');
        }
    }, [clearSession, setSession]);

    const logout = useCallback(async () => {
        try {
            await authApi.logout();
        } finally {
            clearSession();
            router.flushAll();
            router.visit('/login');
        }
    }, [clearSession]);

    useEffect(() => {
        setUnauthorizedHandler(() => {
            clearSession();

            if (!window.location.pathname.startsWith('/login')) {
                router.visit('/login');
            }
        });

        return () => setUnauthorizedHandler(null);
    }, [clearSession]);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            try {
                // Guest pages (login) get 401 — do not trip the global redirect.
                const response = await apiGet<AuthPayload>('/api/me', {
                    skipUnauthorized: true,
                });

                if (!cancelled) {
                    setSession(response.data);
                }
            } catch {
                if (!cancelled) {
                    startTransition(() => {
                        clearSession();
                    });
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [clearSession, setSession]);

    const value = useMemo<AuthContextValue>(
        () => ({
            ...session,
            can: (permission: string) =>
                canPermission(session.permissions, permission),
            canAny: (permissions: string[]) =>
                permissions.some((permission) =>
                    canPermission(session.permissions, permission),
                ),
            canAll: (permissions: string[]) =>
                permissions.every((permission) =>
                    canPermission(session.permissions, permission),
                ),
            setSession,
            clearSession,
            refreshMe,
            logout,
        }),
        [session, setSession, clearSession, refreshMe, logout],
    );

    return (
        <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
    );
}
