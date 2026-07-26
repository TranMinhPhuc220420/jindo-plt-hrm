import type { VariantProps } from 'class-variance-authority';
import { useTranslation } from 'react-i18next';
import type { badgeVariants } from '@/components/ui/badge';
import { Badge } from '@/components/ui/badge';
import type {
    GoalStatus,
    ReviewCycleStatus,
} from '@/lib/api/modules/performance';
import { cn } from '@/lib/utils';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const CYCLE_STATUS_VARIANT: Record<ReviewCycleStatus, BadgeVariant> = {
    draft: 'outline',
    active: 'default',
    finalized: 'secondary',
};

const GOAL_STATUS_VARIANT: Record<GoalStatus, BadgeVariant> = {
    active: 'default',
    completed: 'secondary',
    cancelled: 'outline',
};

export function CycleStatusBadge({ status }: { status: ReviewCycleStatus }) {
    const { t } = useTranslation('performance');

    return (
        <Badge variant={CYCLE_STATUS_VARIANT[status] ?? 'outline'}>
            {t(`status.${status}`, { defaultValue: status })}
        </Badge>
    );
}

export function GoalStatusBadge({ status }: { status: GoalStatus }) {
    const { t } = useTranslation('performance');

    return (
        <Badge variant={GOAL_STATUS_VARIANT[status] ?? 'outline'}>
            {t(`goal_status.${status}`, { defaultValue: status })}
        </Badge>
    );
}

export function ProgressMeter({
    value,
    max = 100,
    label,
    className,
}: {
    value: number;
    max?: number;
    label?: string;
    className?: string;
}) {
    const safeMax = max > 0 ? max : 1;
    const pct = Math.min(100, Math.max(0, Math.round((value / safeMax) * 100)));

    return (
        <div className={cn('space-y-1.5', className)}>
            {label ? (
                <div className="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span>{label}</span>
                    <span className="font-medium text-foreground">{pct}%</span>
                </div>
            ) : null}
            <div
                className="h-2 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuenow={pct}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={label}
            >
                <div
                    className="h-full rounded-full bg-primary transition-[width] duration-300"
                    style={{ width: `${pct}%` }}
                />
            </div>
        </div>
    );
}
