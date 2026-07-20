import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import enAudit from '@/locales/en/audit.json';
import enAuth from '@/locales/en/auth.json';
import enCommon from '@/locales/en/common.json';
import enEmployees from '@/locales/en/employees.json';
import enNav from '@/locales/en/nav.json';
import enOrganization from '@/locales/en/organization.json';
import enPermissions from '@/locales/en/permissions.json';
import enRoles from '@/locales/en/roles.json';
import enSettings from '@/locales/en/settings.json';
import enShifts from '@/locales/en/shifts.json';
import enAttendance from '@/locales/en/attendance.json';
import enLeave from '@/locales/en/leave.json';
import enPayroll from '@/locales/en/payroll.json';
import enDocuments from '@/locales/en/documents.json';
import enAssets from '@/locales/en/assets.json';
import enRecruitment from '@/locales/en/recruitment.json';
import enOnboarding from '@/locales/en/onboarding.json';
import enNotifications from '@/locales/en/notifications.json';
import enReports from '@/locales/en/reports.json';
import enPerformance from '@/locales/en/performance.json';
import enDashboard from '@/locales/en/dashboard.json';
import viAudit from '@/locales/vi/audit.json';
import viAuth from '@/locales/vi/auth.json';
import viCommon from '@/locales/vi/common.json';
import viEmployees from '@/locales/vi/employees.json';
import viNav from '@/locales/vi/nav.json';
import viOrganization from '@/locales/vi/organization.json';
import viPermissions from '@/locales/vi/permissions.json';
import viRoles from '@/locales/vi/roles.json';
import viSettings from '@/locales/vi/settings.json';
import viShifts from '@/locales/vi/shifts.json';
import viAttendance from '@/locales/vi/attendance.json';
import viLeave from '@/locales/vi/leave.json';
import viPayroll from '@/locales/vi/payroll.json';
import viDocuments from '@/locales/vi/documents.json';
import viAssets from '@/locales/vi/assets.json';
import viRecruitment from '@/locales/vi/recruitment.json';
import viOnboarding from '@/locales/vi/onboarding.json';
import viNotifications from '@/locales/vi/notifications.json';
import viReports from '@/locales/vi/reports.json';
import viPerformance from '@/locales/vi/performance.json';
import viDashboard from '@/locales/vi/dashboard.json';

export const SUPPORTED_LOCALES = ['vi', 'en'] as const;
export type AppLocale = (typeof SUPPORTED_LOCALES)[number];
export const DEFAULT_LOCALE: AppLocale = 'vi';

const resources = {
    vi: {
        common: viCommon,
        nav: viNav,
        auth: viAuth,
        employees: viEmployees,
        organization: viOrganization,
        roles: viRoles,
        permissions: viPermissions,
        settings: viSettings,
        audit: viAudit,
        shifts: viShifts,
        attendance: viAttendance,
        leave: viLeave,
        payroll: viPayroll,
        documents: viDocuments,
        assets: viAssets,
        recruitment: viRecruitment,
        onboarding: viOnboarding,
        notifications: viNotifications,
        reports: viReports,
        performance: viPerformance,
        dashboard: viDashboard,
    },
    en: {
        common: enCommon,
        nav: enNav,
        auth: enAuth,
        employees: enEmployees,
        organization: enOrganization,
        roles: enRoles,
        permissions: enPermissions,
        settings: enSettings,
        audit: enAudit,
        shifts: enShifts,
        attendance: enAttendance,
        leave: enLeave,
        payroll: enPayroll,
        documents: enDocuments,
        assets: enAssets,
        recruitment: enRecruitment,
        onboarding: enOnboarding,
        notifications: enNotifications,
        reports: enReports,
        performance: enPerformance,
        dashboard: enDashboard,
    },
};

void i18n.use(initReactI18next).init({
    resources,
    lng: DEFAULT_LOCALE,
    fallbackLng: 'en',
    defaultNS: 'common',
    ns: [
        'common',
        'nav',
        'auth',
        'employees',
        'organization',
        'roles',
        'permissions',
        'settings',
        'audit',
        'shifts',
        'attendance',
        'leave',
        'payroll',
        'documents',
        'assets',
        'recruitment',
        'onboarding',
        'notifications',
        'reports',
        'performance',
        'dashboard',
    ],
    interpolation: {
        escapeValue: false,
    },
});

export function isAppLocale(value: string): value is AppLocale {
    return (SUPPORTED_LOCALES as readonly string[]).includes(value);
}

export async function applyLocale(locale: string): Promise<void> {
    const next = isAppLocale(locale) ? locale : DEFAULT_LOCALE;
    await i18n.changeLanguage(next);
    document.documentElement.lang = next;
}

export default i18n;
