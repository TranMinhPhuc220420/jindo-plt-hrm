import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as performanceApi from '@/lib/api/modules/performance';
import type {
    PerformanceEvaluation,
    PerformanceGoal,
    ReviewCycle,
} from '@/lib/api/modules/performance';

type Props = {
    id: number;
};

export default function PerformanceCycleShowPage({ id }: Props) {
    const { t, i18n } = useTranslation(['performance', 'common']);
    const [cycle, setCycle] = useState<ReviewCycle | null>(null);
    const [goals, setGoals] = useState<PerformanceGoal[]>([]);
    const [evaluations, setEvaluations] = useState<PerformanceEvaluation[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [goalEmployeeId, setGoalEmployeeId] = useState<number | null>(null);
    const [goalTitle, setGoalTitle] = useState('');

    const [evalEmployeeId, setEvalEmployeeId] = useState<number | null>(null);
    const [evalScore, setEvalScore] = useState('');
    const [evalSummary, setEvalSummary] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [cycleData, goalsData, evalData] = await Promise.all([
                performanceApi.getCycle(id),
                performanceApi.listGoals({
                    review_cycle_id: id,
                    per_page: 100,
                }),
                performanceApi.listEvaluations({
                    review_cycle_id: id,
                    per_page: 100,
                }),
            ]);
            setCycle(cycleData ?? null);
            setGoals(goalsData.data);
            setEvaluations(evalData.data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('error_load'));
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useEffect(() => {
        void load();
    }, [load]);

    async function withBusy(fn: () => Promise<void>) {
        setBusy(true);

        try {
            await fn();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleStart() {
        await withBusy(async () => {
            await performanceApi.startCycle(id);
            toast.success(t('toast_started'));
            await load();
        });
    }

    async function handleFinalize() {
        await withBusy(async () => {
            await performanceApi.finalizeCycle(id);
            toast.success(t('toast_finalized'));
            await load();
        });
    }

    async function handleAddGoal(e: React.FormEvent) {
        e.preventDefault();

        if (goalEmployeeId === null) {
            return;
        }

        await withBusy(async () => {
            await performanceApi.createGoal({
                employee_id: goalEmployeeId,
                review_cycle_id: id,
                title: goalTitle,
            });
            toast.success(t('toast_goal_created'));
            setGoalEmployeeId(null);
            setGoalTitle('');
            await load();
        });
    }

    async function handleSubmitEvaluation(e: React.FormEvent) {
        e.preventDefault();

        if (evalEmployeeId === null) {
            return;
        }

        await withBusy(async () => {
            await performanceApi.submitEvaluation({
                review_cycle_id: id,
                employee_id: evalEmployeeId,
                overall_score: Number(evalScore),
                summary: evalSummary || undefined,
            });
            toast.success(t('toast_evaluation_submitted'));
            setEvalEmployeeId(null);
            setEvalScore('');
            setEvalSummary('');
            await load();
        });
    }

    return (
        <AdminPageShell
            title={t('show_title')}
            description={t('show_description')}
            permission="can_view_performance"
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/performance">{t('back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error || !cycle ? (
                <ErrorState message={error ?? t('error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-muted-foreground">
                                {t('col_name')}
                            </p>
                            <p className="font-medium">{cycle.name}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('col_status')}
                            </p>
                            <p>
                                {t(`status.${cycle.status}`, {
                                    defaultValue: cycle.status,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('col_framework')}
                            </p>
                            <p>
                                {t(`framework_option.${cycle.framework}`, {
                                    defaultValue: cycle.framework,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('col_period')}
                            </p>
                            <p>
                                {cycle.starts_on ?? '—'}
                                {cycle.ends_on ? ` → ${cycle.ends_on}` : ''}
                            </p>
                        </div>
                    </div>

                    <PermissionGate permission="can_manage_review_cycles">
                        <div className="flex flex-wrap gap-2">
                            {cycle.status === 'draft' && (
                                <Button
                                    size="sm"
                                    disabled={busy}
                                    onClick={() => void handleStart()}
                                >
                                    {t('start_cycle')}
                                </Button>
                            )}
                            {cycle.status === 'active' && (
                                <Button
                                    size="sm"
                                    disabled={busy}
                                    onClick={() => void handleFinalize()}
                                >
                                    {t('finalize_cycle')}
                                </Button>
                            )}
                        </div>
                    </PermissionGate>

                    <section className="space-y-3">
                        <h2 className="text-sm font-medium">
                            {t('goals_title')}
                        </h2>

                        <PermissionGate permission="can_manage_goals">
                            <form
                                onSubmit={handleAddGoal}
                                className="grid gap-3 rounded-lg border border-border p-4 sm:grid-cols-[minmax(14rem,1fr)_1fr_auto] sm:items-end"
                            >
                                <EmployeePickerField
                                    id="goal_employee"
                                    label={t('employee_id')}
                                    value={goalEmployeeId}
                                    onChange={(id) => setGoalEmployeeId(id)}
                                    required
                                />
                                <div className="grid gap-1.5">
                                    <Label htmlFor="goal_title">
                                        {t('goal_title_label')}
                                    </Label>
                                    <Input
                                        id="goal_title"
                                        value={goalTitle}
                                        onChange={(e) =>
                                            setGoalTitle(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={busy || goalEmployeeId === null}
                                >
                                    {t('add_goal')}
                                </Button>
                            </form>
                        </PermissionGate>

                        {goals.length === 0 ? (
                            <EmptyState message={t('empty_goals')} />
                        ) : (
                            <ul className="space-y-2">
                                {goals.map((goal) => (
                                    <li
                                        key={goal.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/60 p-3 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {goal.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {goal.employee_name ??
                                                    `#${goal.employee_id}`}{' '}
                                                ·{' '}
                                                {t(
                                                    `goal_status.${goal.status}`,
                                                    {
                                                        defaultValue:
                                                            goal.status,
                                                    },
                                                )}
                                            </p>
                                        </div>
                                        <span className="text-muted-foreground">
                                            {goal.progress}%
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3">
                        <h2 className="text-sm font-medium">
                            {t('evaluations_title')}
                        </h2>

                        <PermissionGate permission="can_evaluate_employee">
                            <form
                                onSubmit={handleSubmitEvaluation}
                                className="grid gap-3 rounded-lg border border-border p-4 sm:grid-cols-2"
                            >
                                <EmployeePickerField
                                    id="eval_employee"
                                    label={t('employee_id')}
                                    value={evalEmployeeId}
                                    onChange={(id) => setEvalEmployeeId(id)}
                                    required
                                />
                                <div className="grid gap-1.5">
                                    <Label htmlFor="eval_score">
                                        {t('overall_score')}
                                    </Label>
                                    <Input
                                        id="eval_score"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="5"
                                        value={evalScore}
                                        onChange={(e) =>
                                            setEvalScore(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label htmlFor="eval_summary">
                                        {t('summary')}
                                    </Label>
                                    <Input
                                        id="eval_summary"
                                        value={evalSummary}
                                        onChange={(e) =>
                                            setEvalSummary(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={
                                            busy || evalEmployeeId === null
                                        }
                                    >
                                        {t('submit_evaluation')}
                                    </Button>
                                </div>
                            </form>
                        </PermissionGate>

                        {evaluations.length === 0 ? (
                            <EmptyState message={t('empty_evaluations')} />
                        ) : (
                            <ul className="space-y-2">
                                {evaluations.map((evaluation) => (
                                    <li
                                        key={evaluation.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/60 p-3 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {evaluation.employee_name ??
                                                    `#${evaluation.employee_id}`}
                                            </p>
                                            {evaluation.summary && (
                                                <p className="text-muted-foreground">
                                                    {evaluation.summary}
                                                </p>
                                            )}
                                            {evaluation.submitted_at && (
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        evaluation.submitted_at,
                                                    ).toLocaleString(
                                                        i18n.language,
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        <span className="font-medium">
                                            {evaluation.overall_score}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            )}
        </AdminPageShell>
    );
}
