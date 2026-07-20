import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as onboardingApi from '@/lib/api/modules/onboarding';
import type {
    OnboardingCase,
    OnboardingTask,
} from '@/lib/api/modules/onboarding';
import {
    onboardingAssigneeLabel,
    onboardingProgressLabel,
    onboardingTaskTitle,
} from '@/lib/i18n/onboarding-labels';

type Props = {
    id: number;
};

export default function OnboardingShowPage({ id }: Props) {
    const { t } = useTranslation(['onboarding', 'common']);
    const [onboardingCase, setOnboardingCase] = useState<OnboardingCase | null>(
        null,
    );
    const [tasks, setTasks] = useState<OnboardingTask[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const caseData = await onboardingApi.getCase(id);
            setOnboardingCase(caseData ?? null);
            setTasks(caseData?.tasks ?? []);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useLoadEffect(load, [load]);

    async function withBusy(fn: () => Promise<void>) {
        setBusy(true);

        try {
            await fn();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('show.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleTask(task: OnboardingTask) {
        await withBusy(async () => {
            if (task.status === 'done') {
                await onboardingApi.reopenTask(task.id);
                toast.success(t('show.toast_reopened'));
            } else {
                await onboardingApi.completeTask(task.id);
                toast.success(t('show.toast_task_done'));
            }

            await load();
        });
    }

    async function handleCompleteCase() {
        await withBusy(async () => {
            await onboardingApi.completeCase(id);
            toast.success(t('show.toast_completed'));
            await load();
        });
    }

    const mandatoryRemaining =
        onboardingCase?.progress?.mandatory_remaining ?? 0;
    const emptyValue = t('empty_value', { ns: 'common' });

    return (
        <AdminPageShell
            title={t('show.title')}
            description={t('show.description')}
            any={['can_view_onboarding', 'can_manage_onboarding']}
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/onboarding">{t('show.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('show.loading')} />
            ) : error || !onboardingCase ? (
                <ErrorState message={error ?? t('show.error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.employee')}
                            </p>
                            <p>#{onboardingCase.employee_id}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.status')}
                            </p>
                            <p>
                                {t(`status.${onboardingCase.status}`, {
                                    defaultValue: onboardingCase.status,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.progress')}
                            </p>
                            <p>
                                {onboardingProgressLabel(
                                    t,
                                    onboardingCase.progress,
                                )}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.probation_ends')}
                            </p>
                            <p>
                                {onboardingCase.probation_ends_on ?? emptyValue}
                            </p>
                        </div>
                    </div>

                    <div>
                        <h2 className="mb-3 text-sm font-medium">
                            {t('show.tasks_title')}
                        </h2>
                        {tasks.length === 0 ? (
                            <EmptyState message={t('show.empty_tasks')} />
                        ) : (
                            <ul className="space-y-2">
                                {tasks.map((task) => {
                                    const assignee = onboardingAssigneeLabel(
                                        t,
                                        task.assignee_type,
                                    );

                                    return (
                                        <li
                                            key={task.id}
                                            className="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-2 text-sm"
                                        >
                                            <div>
                                                <p>
                                                    {onboardingTaskTitle(
                                                        t,
                                                        task.key,
                                                        task.title,
                                                    )}
                                                    {task.mandatory ? (
                                                        <span className="ml-2 text-xs text-destructive">
                                                            {t(
                                                                'show.mandatory',
                                                            )}
                                                        </span>
                                                    ) : null}
                                                </p>
                                                <p className="text-muted-foreground">
                                                    {t(
                                                        `task_status.${task.status}`,
                                                        {
                                                            defaultValue:
                                                                task.status,
                                                        },
                                                    )}
                                                    {assignee
                                                        ? ` · ${t('show.assignee')}: ${assignee}`
                                                        : null}
                                                </p>
                                            </div>
                                            <PermissionGate
                                                any={[
                                                    'can_complete_onboarding_task',
                                                    'can_manage_onboarding',
                                                ]}
                                            >
                                                <Button
                                                    variant={
                                                        task.status === 'done'
                                                            ? 'ghost'
                                                            : 'outline'
                                                    }
                                                    size="sm"
                                                    disabled={
                                                        busy ||
                                                        onboardingCase.status !==
                                                            'in_progress'
                                                    }
                                                    onClick={() =>
                                                        void handleTask(task)
                                                    }
                                                >
                                                    {task.status === 'done'
                                                        ? t('show.reopen')
                                                        : t(
                                                              'show.complete_task',
                                                          )}
                                                </Button>
                                            </PermissionGate>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>

                    <PermissionGate permission="can_complete_onboarding">
                        <div className="border-t border-border pt-6">
                            <Button
                                disabled={
                                    busy ||
                                    onboardingCase.status !== 'in_progress' ||
                                    mandatoryRemaining > 0
                                }
                                onClick={() => void handleCompleteCase()}
                            >
                                {t('show.complete_case')}
                            </Button>
                            {mandatoryRemaining > 0 ? (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {t('show.mandatory_pending', {
                                        count: mandatoryRemaining,
                                    })}
                                </p>
                            ) : null}
                        </div>
                    </PermissionGate>
                </div>
            )}
        </AdminPageShell>
    );
}
