import { useTranslation } from 'react-i18next';
import { WorkingSchedulePreview } from '@/components/my-schedule/working-schedule-preview';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { EmptyState } from '@/components/shared/async-state';
import { useAuth } from '@/lib/auth/auth-context';

export default function MySchedulePage() {
    const { t } = useTranslation(['shifts', 'common']);
    const { employeeId } = useAuth();

    return (
        <AdminPageShell
            title={t('my_schedule.title')}
            description={t('my_schedule.description')}
            permission="can_view_own_schedule"
        >
            {!employeeId ? (
                <EmptyState message={t('my_schedule.no_employee')} />
            ) : (
                <WorkingSchedulePreview employeeId={employeeId} />
            )}
        </AdminPageShell>
    );
}
