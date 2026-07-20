import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    Building2,
    CalendarCheck2,
    CalendarDays,
    CalendarOff,
    ClipboardList,
    Clock3,
    FileText,
    LayoutGrid,
    Laptop,
    Settings2,
    Shield,
    Target,
    UserPlus,
    Users,
    UsersRound,
    Wallet,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAuth } from '@/lib/auth/auth-context';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useTranslation('nav');
    const { can, canAny, isLoading } = useAuth();

    const mainNavItems: NavItem[] = [
        {
            title: t('dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('employees'),
            href: '/employees',
            icon: Users,
            permission: 'can_view_employee',
        },
        {
            title: t('shifts'),
            href: '/shifts',
            icon: Clock3,
            permission: 'can_view_shifts',
        },
        {
            title: t('my_schedule'),
            href: '/my-schedule',
            icon: CalendarDays,
            permission: 'can_view_own_schedule',
        },
        {
            title: t('attendance'),
            href: '/attendance',
            icon: CalendarCheck2,
            permission: 'can_view_attendance',
        },
        {
            title: t('leave'),
            href: '/leave',
            icon: CalendarOff,
            permission: 'can_view_leave',
        },
        {
            title: t('payroll'),
            href: can('can_view_payroll_history')
                ? '/payroll'
                : '/payroll/payslips',
            icon: Wallet,
            permission: 'can_view_payroll_history',
        },
        {
            title: t('performance'),
            href: '/performance',
            icon: Target,
            permission: 'can_view_performance',
        },
        {
            title: t('reports'),
            href: '/reports',
            icon: BarChart3,
            permission: 'can_view_reports',
        },
        {
            title: t('notifications'),
            href: '/notifications',
            icon: Bell,
            permission: 'can_view_own_notifications',
        },
        {
            title: t('recruitment'),
            href: '/recruitment',
            icon: UserPlus,
            permission: 'can_view_candidates',
        },
        {
            title: t('onboarding'),
            href: '/onboarding',
            icon: UsersRound,
            permission: 'can_view_onboarding',
        },
        {
            title: t('assets'),
            href: '/assets',
            icon: Laptop,
            permission: 'can_view_assets',
        },
        {
            title: t('documents'),
            href: '/documents',
            icon: FileText,
            permission: 'can_view_company_documents',
        },
        {
            title: t('organization'),
            href: '/organization',
            icon: Building2,
            permission: 'can_view_organization',
        },
        {
            title: t('roles'),
            href: '/roles',
            icon: Shield,
            permission: 'can_view_roles',
        },
        {
            title: t('settings'),
            href: '/settings/company',
            icon: Settings2,
            permission: 'can_view_settings',
        },
        {
            title: t('audit'),
            href: '/audit-logs',
            icon: ClipboardList,
            permission: 'can_view_audit_logs',
        },
    ];

    const visibleItems = mainNavItems.filter((item) => {
        if (!item.permission) {
            return true;
        }

        // While permissions hydrate from /api/me, hide gated items to avoid flash.
        if (isLoading) {
            return false;
        }

        if (item.permission === 'can_view_payroll_history') {
            return can('can_view_payroll_history') || can('can_view_salary');
        }

        if (item.permission === 'can_view_reports') {
            return canAny([
                'can_view_attendance_reports',
                'can_view_payroll_reports',
                'can_view_leave_reports',
                'can_view_employee_reports',
                'can_view_performance_reports',
                'can_export_reports',
            ]);
        }

        if (item.permission === 'can_view_candidates') {
            return (
                can('can_view_candidates') ||
                can('can_manage_candidates') ||
                can('can_manage_job_positions')
            );
        }

        if (item.permission === 'can_view_onboarding') {
            return can('can_view_onboarding') || can('can_manage_onboarding');
        }

        if (item.permission === 'can_view_assets') {
            return can('can_view_assets') || can('can_manage_assets');
        }

        if (item.permission === 'can_view_company_documents') {
            return (
                can('can_view_company_documents') ||
                can('can_view_employee_documents') ||
                can('can_upload_own_documents')
            );
        }

        return can(item.permission);
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleItems} label={t('platform')} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
