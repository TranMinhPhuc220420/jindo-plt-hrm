import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it } from 'vitest';
import { PermissionGate } from '@/components/shared/permission-gate';
import {
    AuthContext,
    canPermission,
    emptyAuthSession,
} from '@/lib/auth/auth-context';
import type { AuthContextValue } from '@/lib/auth/auth-context';

function wrapper(permissions: string[]) {
    const value: AuthContextValue = {
        ...emptyAuthSession,
        permissions,
        can: (permission) => canPermission(permissions, permission),
        canAny: (keys) => keys.some((key) => canPermission(permissions, key)),
        canAll: (keys) => keys.every((key) => canPermission(permissions, key)),
        setSession: () => undefined,
        clearSession: () => undefined,
        refreshMe: async () => undefined,
        logout: async () => undefined,
    };

    return function AuthWrapper({ children }: { children: ReactNode }) {
        return (
            <AuthContext.Provider value={value}>
                {children}
            </AuthContext.Provider>
        );
    };
}

describe('canPermission', () => {
    it('returns true when the key is present', () => {
        expect(canPermission(['can_view_employee'], 'can_view_employee')).toBe(
            true,
        );
    });

    it('returns false when the key is missing', () => {
        expect(canPermission(['can_view_leave'], 'can_view_employee')).toBe(
            false,
        );
    });
});

describe('PermissionGate', () => {
    it('renders children when permission is granted', () => {
        render(
            <PermissionGate permission="can_create_employee">
                <button type="button">Create</button>
            </PermissionGate>,
            { wrapper: wrapper(['can_create_employee']) },
        );

        expect(
            screen.getByRole('button', { name: 'Create' }),
        ).toBeInTheDocument();
    });

    it('renders fallback when permission is denied', () => {
        render(
            <PermissionGate
                permission="can_create_employee"
                fallback={<span>Denied</span>}
            >
                <button type="button">Create</button>
            </PermissionGate>,
            { wrapper: wrapper(['can_view_employee']) },
        );

        expect(screen.getByText('Denied')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Create' })).toBeNull();
    });

    it('supports any/all permission lists', () => {
        const { rerender } = render(
            <PermissionGate any={['can_view_leave', 'can_approve_leave']}>
                <span>Any ok</span>
            </PermissionGate>,
            { wrapper: wrapper(['can_approve_leave']) },
        );
        expect(screen.getByText('Any ok')).toBeInTheDocument();

        rerender(
            <AuthContext.Provider
                value={{
                    ...emptyAuthSession,
                    permissions: ['can_view_leave'],
                    can: (p) => canPermission(['can_view_leave'], p),
                    canAny: (keys) =>
                        keys.some((key) =>
                            canPermission(['can_view_leave'], key),
                        ),
                    canAll: (keys) =>
                        keys.every((key) =>
                            canPermission(['can_view_leave'], key),
                        ),
                    setSession: () => undefined,
                    clearSession: () => undefined,
                    refreshMe: async () => undefined,
                    logout: async () => undefined,
                }}
            >
                <PermissionGate all={['can_view_leave', 'can_approve_leave']}>
                    <span>All ok</span>
                </PermissionGate>
            </AuthContext.Provider>,
        );
        expect(screen.queryByText('All ok')).toBeNull();
    });
});
