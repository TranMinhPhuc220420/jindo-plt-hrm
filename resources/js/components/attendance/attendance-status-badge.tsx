import type { VariantProps } from 'class-variance-authority';
import { useTranslation } from 'react-i18next';
import { Badge, badgeVariants } from '@/components/ui/badge';
import type { AttendanceStatus } from '@/lib/api/modules/attendance';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const STATUS_VARIANT: Record<AttendanceStatus | 'pending' | 'approved' | 'rejected', BadgeVariant> = {
    open: 'outline',
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
    locked: 'outline',
};

type Props = {
    status: string;
};

export function AttendanceStatusBadge({ status }: Props) {
    const { t } = useTranslation('attendance');
    const variant =
        STATUS_VARIANT[status as keyof typeof STATUS_VARIANT] ?? 'outline';

    return (
        <Badge variant={variant}>
            {t(`status.${status}`, { defaultValue: status })}
        </Badge>
    );
}
