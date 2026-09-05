import { createContext, useContext } from 'react';
import type { AuthUser } from '@/lib/api/types';

export type AuthSession = {
    user: AuthUser | null;
    permissions: string[];
    employeeId: number | null;
    twoFactorRequired: boolean;
    locale: string | null;
    userLocale: string | null;
    companyLocale: string | null;
    vapidPublicKey: string | null;
    isLoading: boolean;
};

export type AuthContextValue = AuthSession & {
    can: (permission: string) => boolean;
    canAny: (permissions: string[]) => boolean;
    canAll: (permissions: string[]) => boolean;
    setSession: (payload: {
        user: AuthUser;
        permissions: string[];
        employee_id?: number | null;
        two_factor_required?: boolean;
        locale?: string;
        user_locale?: string | null;
        company_locale?: string;
        vapid_public_key?: string | null;
    }) => void;
    clearSession: () => void;
    refreshMe: () => Promise<void>;
    logout: () => Promise<void>;
};

export const emptyAuthSession: AuthSession = {
    user: null,
    permissions: [],
    employeeId: null,
    twoFactorRequired: false,
    locale: null,
    userLocale: null,
    companyLocale: null,
    vapidPublicKey: null,
    isLoading: false,
};

export const AuthContext = createContext<AuthContextValue | null>(null);

export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}

export function canPermission(
    permissions: string[],
    permission: string,
): boolean {
    return permissions.includes(permission);
}
