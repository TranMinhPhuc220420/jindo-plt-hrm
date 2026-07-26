import { Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    CheckIcon,
    PlusIcon,
    XIcon,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    CycleStatusBadge,
    GoalStatusBadge,
    ProgressMeter,
} from '@/components/performance/performance-status';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { EmployeePickerDialog } from '@/components/shared/employee-picker-dialog';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import type { Employee } from '@/lib/api/modules/employees';
import * as performanceApi from '@/lib/api/modules/performance';
import type {
    GoalStatus,
    PerformanceEvaluation,
    PerformanceGoal,
    PromotionSuggestion,
    ReviewCycle,
    ReviewCycleParticipant,
} from '@/lib/api/modules/performance';
import { cn } from '@/lib/utils';

type Props = {
    id: number;
};

const GOAL_STATUSES: GoalStatus[] = ['active', 'completed', 'cancelled'];

function participantLabel(participant: ReviewCycleParticipant): string {
    const name = participant.employee_name ?? `#${participant.employee_id}`;

    return participant.employee_code
        ? `${participant.employee_code} — ${name}`
        : name;
}

function SectionHeader({
    title,
    count,
    action,
}: {
    title: string;
    count?: number;
    action?: React.ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2">
                <h2 className="text-sm font-medium">{title}</h2>
                {typeof count === 'number' ? (
                    <Badge variant="secondary">{count}</Badge>
                ) : null}
            </div>
            {action}
        </div>
    );
}

export default function PerformanceCycleShowPage({ id }: Props) {
    const { t, i18n } = useTranslation(['performance', 'common']);
    const [cycle, setCycle] = useState<ReviewCycle | null>(null);
    const [goals, setGoals] = useState<PerformanceGoal[]>([]);
    const [evaluations, setEvaluations] = useState<PerformanceEvaluation[]>([]);
    const [suggestions, setSuggestions] = useState<PromotionSuggestion[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);

    const [goalEmployeeId, setGoalEmployeeId] = useState<number | null>(null);
    const [goalTitle, setGoalTitle] = useState('');

    const [evalEmployeeId, setEvalEmployeeId] = useState<number | null>(null);
    const [evalScore, setEvalScore] = useState('');
    const [evalSummary, setEvalSummary] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [cycleData, goalsData, evalData, suggestionData] =
                await Promise.all([
                    performanceApi.getCycle(id),
                    performanceApi.listGoals({
                        review_cycle_id: id,
                        per_page: 100,
                    }),
                    performanceApi.listEvaluations({
                        review_cycle_id: id,
                        per_page: 100,
                    }),
                    performanceApi
                        .listPromotionSuggestions({
                            review_cycle_id: id,
                            per_page: 100,
                        })
                        .catch(() => ({ data: [] as PromotionSuggestion[] })),
                ]);
            setCycle(cycleData ?? null);
            setGoals(goalsData.data);
            setEvaluations(evalData.data);
            setSuggestions(suggestionData.data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('error_load'));
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
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    const participants = cycle?.participants ?? [];
    const participantIds =
        cycle?.participant_employee_ids ??
        participants.map((p) => p.employee_id);
    const participantsCount =
        cycle?.participants_count ?? participantIds.length;
    const evaluationsCount = cycle?.evaluations_count ?? evaluations.length;
    const goalsActiveCount =
        cycle?.goals_active_count ??
        goals.filter((g) => g.status === 'active').length;
    const goalsCompletedCount =
        cycle?.goals_completed_count ??
        goals.filter((g) => g.status === 'completed').length;

    const evaluatedEmployeeIds = new Set(
        evaluations.map((evaluation) => evaluation.employee_id),
    );
    const unevaluatedParticipants = participants.filter(
        (p) => !evaluatedEmployeeIds.has(p.employee_id),
    );
    const incomplete =
        cycle?.status === 'active' && evaluationsCount < participantsCount;

    async function handleStart() {
        if (participantsCount < 1) {
            toast.error(t('participants_required'));

            return;
        }

        await withBusy(async () => {
            await performanceApi.startCycle(id);
            toast.success(t('toast_started'));
            await load();
        });
    }

    async function handleFinalize() {
        if (evaluationsCount < participantsCount) {
            const confirmed = window.confirm(
                t('confirm_finalize_incomplete', {
                    evaluated: evaluationsCount,
                    total: participantsCount,
                }),
            );

            if (!confirmed) {
                return;
            }
        }

        await withBusy(async () => {
            await performanceApi.finalizeCycle(id);
            toast.success(t('toast_finalized'));
            await load();
        });
    }

    async function handleDelete() {
        const confirmed = window.confirm(t('confirm_delete_draft'));

        if (!confirmed) {
            return;
        }

        await withBusy(async () => {
            await performanceApi.deleteCycle(id);
            toast.success(t('toast_deleted'));
            router.visit('/performance');
        });
    }

    async function syncParticipants(nextIds: number[]) {
        await withBusy(async () => {
            await performanceApi.syncCycleParticipants(id, nextIds);
            toast.success(t('toast_participants_updated'));
            await load();
        });
    }

    async function handleAddParticipant(employee: Employee) {
        if (participantIds.includes(employee.id)) {
            return;
        }

        await syncParticipants([...participantIds, employee.id]);
    }

    async function handleRemoveParticipant(employeeId: number) {
        await syncParticipants(
            participantIds.filter((pid) => pid !== employeeId),
        );
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

    async function handleGoalProgressBlur(
        goal: PerformanceGoal,
        progressRaw: string,
    ) {
        const progress = Math.min(
            100,
            Math.max(0, Number.parseInt(progressRaw, 10) || 0),
        );

        if (progress === goal.progress) {
            return;
        }

        await withBusy(async () => {
            await performanceApi.updateGoal(goal.id, { progress });
            toast.success(t('toast_goal_updated'));
            await load();
        });
    }

    async function handleGoalStatusChange(
        goal: PerformanceGoal,
        status: GoalStatus,
    ) {
        if (status === goal.status) {
            return;
        }

        await withBusy(async () => {
            await performanceApi.updateGoal(goal.id, {
                status,
                ...(status === 'completed' && goal.progress < 100
                    ? { progress: 100 }
                    : {}),
            });
            toast.success(t('toast_goal_updated'));
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

    async function handleAcknowledge(suggestionId: number) {
        await withBusy(async () => {
            await performanceApi.acknowledgePromotionSuggestion(suggestionId);
            toast.success(t('toast_suggestion_acknowledged'));
            await load();
        });
    }

    return (
        <AdminPageShell
            title={cycle?.name ?? t('show_title')}
            description={t('show_description')}
            permission="can_view_performance"
            actions={
                cycle ? (
                    <div className="flex flex-wrap items-center gap-2">
                        <CycleStatusBadge status={cycle.status} />
                        <PermissionGate permission="can_manage_review_cycles">
                            {cycle.status === 'draft' ? (
                                <Button
                                    size="sm"
                                    disabled={busy || participantsCount < 1}
                                    onClick={() => void handleStart()}
                                >
                                    {t('start_cycle')}
                                </Button>
                            ) : null}
                            {cycle.status === 'draft' ? (
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    disabled={busy}
                                    onClick={() => void handleDelete()}
                                >
                                    {t('delete_cycle')}
                                </Button>
                            ) : null}
                            {cycle.status === 'active' ? (
                                <Button
                                    size="sm"
                                    disabled={busy}
                                    onClick={() => void handleFinalize()}
                                >
                                    {t('finalize_cycle')}
                                </Button>
                            ) : null}
                        </PermissionGate>
                    </div>
                ) : null
            }
        >
            <div className="mb-6">
                <Button variant="ghost" size="sm" className="-ml-2" asChild>
                    <Link href="/performance">
                        <ArrowLeftIcon className="size-4" />
                        {t('back')}
                    </Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error || !cycle ? (
                <ErrorState message={error ?? t('error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-3 rounded-lg border border-border bg-muted/20 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                {t('col_framework')}
                            </p>
                            <p className="mt-1 font-medium">
                                {t(`framework_option.${cycle.framework}`, {
                                    defaultValue: cycle.framework,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                {t('col_period')}
                            </p>
                            <p className="mt-1 font-medium">
                                {cycle.starts_on ?? '—'}
                                {cycle.ends_on ? ` → ${cycle.ends_on}` : ''}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                {t('col_participants')}
                            </p>
                            <p className="mt-1 font-medium">
                                {participantsCount}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                {t('summary_evaluated')}
                            </p>
                            <p className="mt-1 font-medium">
                                {evaluationsCount} / {participantsCount}
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs text-muted-foreground">
                                {t('summary_evaluated')}
                            </p>
                            <p className="mt-1 text-2xl font-semibold tracking-tight">
                                {evaluationsCount}
                                <span className="text-base font-normal text-muted-foreground">
                                    {' '}
                                    / {participantsCount}
                                </span>
                            </p>
                            <ProgressMeter
                                className="mt-3"
                                value={evaluationsCount}
                                max={Math.max(participantsCount, 1)}
                            />
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs text-muted-foreground">
                                {t('summary_goals_active')}
                            </p>
                            <p className="mt-1 text-2xl font-semibold tracking-tight">
                                {goalsActiveCount}
                            </p>
                            <p className="mt-3 text-xs text-muted-foreground">
                                {t('summary_goals_active_hint')}
                            </p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs text-muted-foreground">
                                {t('summary_goals_completed')}
                            </p>
                            <p className="mt-1 text-2xl font-semibold tracking-tight">
                                {goalsCompletedCount}
                            </p>
                            <p className="mt-3 text-xs text-muted-foreground">
                                {t('summary_goals_completed_hint')}
                            </p>
                        </div>
                    </div>

                    {incomplete ? (
                        <div className="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm">
                            {t('incomplete_notice', {
                                remaining:
                                    participantsCount - evaluationsCount,
                            })}
                        </div>
                    ) : null}

                    {cycle.status === 'draft' && participantsCount < 1 ? (
                        <div className="rounded-lg border border-dashed border-border px-4 py-3 text-sm text-muted-foreground">
                            {t('draft_needs_participants')}
                        </div>
                    ) : null}

                    <section className="space-y-3">
                        <SectionHeader
                            title={t('participants_title')}
                            count={participants.length}
                            action={
                                cycle.status === 'draft' ? (
                                    <PermissionGate permission="can_manage_review_cycles">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={busy}
                                            onClick={() => setPickerOpen(true)}
                                        >
                                            <PlusIcon className="size-3.5" />
                                            {t('add_participant')}
                                        </Button>
                                    </PermissionGate>
                                ) : null
                            }
                        />

                        {participants.length === 0 ? (
                            <EmptyState message={t('empty_participants')} />
                        ) : (
                            <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border">
                                {participants.map((participant) => {
                                    const evaluated = evaluatedEmployeeIds.has(
                                        participant.employee_id,
                                    );

                                    return (
                                        <li
                                            key={participant.employee_id}
                                            className="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm"
                                        >
                                            <div className="min-w-0">
                                                <p className="font-medium">
                                                    {participant.employee_name ??
                                                        `#${participant.employee_id}`}
                                                </p>
                                                {participant.employee_code ? (
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            participant.employee_code
                                                        }
                                                    </p>
                                                ) : null}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {cycle.status !== 'draft' ? (
                                                    <Badge
                                                        variant={
                                                            evaluated
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {evaluated ? (
                                                            <>
                                                                <CheckIcon className="size-3" />
                                                                {t(
                                                                    'participant_evaluated',
                                                                )}
                                                            </>
                                                        ) : (
                                                            t(
                                                                'participant_pending',
                                                            )
                                                        )}
                                                    </Badge>
                                                ) : null}
                                                {cycle.status === 'draft' ? (
                                                    <PermissionGate permission="can_manage_review_cycles">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={busy}
                                                            onClick={() =>
                                                                void handleRemoveParticipant(
                                                                    participant.employee_id,
                                                                )
                                                            }
                                                            aria-label={t(
                                                                'remove_participant',
                                                            )}
                                                        >
                                                            <XIcon className="size-4" />
                                                        </Button>
                                                    </PermissionGate>
                                                ) : null}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeader
                            title={t('goals_title')}
                            count={goals.length}
                        />

                        <PermissionGate permission="can_manage_goals">
                            <form
                                onSubmit={handleAddGoal}
                                className="grid gap-3 rounded-lg border border-border bg-muted/10 p-4 sm:grid-cols-[minmax(14rem,1fr)_1fr_auto] sm:items-end"
                            >
                                <div className="grid gap-1.5">
                                    <Label htmlFor="goal_employee">
                                        {t('employee_id')}
                                    </Label>
                                    <select
                                        id="goal_employee"
                                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        value={goalEmployeeId ?? ''}
                                        onChange={(e) =>
                                            setGoalEmployeeId(
                                                e.target.value
                                                    ? Number(e.target.value)
                                                    : null,
                                            )
                                        }
                                        required
                                        disabled={participants.length === 0}
                                    >
                                        <option value="">
                                            {t('select_participant')}
                                        </option>
                                        {participants.map((participant) => (
                                            <option
                                                key={participant.employee_id}
                                                value={participant.employee_id}
                                            >
                                                {participantLabel(participant)}
                                            </option>
                                        ))}
                                    </select>
                                </div>
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
                                        placeholder={t(
                                            'goal_title_placeholder',
                                        )}
                                        required
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={
                                        busy ||
                                        goalEmployeeId === null ||
                                        participants.length === 0
                                    }
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
                                        className="rounded-lg border border-border p-3 text-sm"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-medium">
                                                        {goal.title}
                                                    </p>
                                                    <GoalStatusBadge
                                                        status={goal.status}
                                                    />
                                                </div>
                                                <p className="mt-0.5 text-muted-foreground">
                                                    {goal.employee_name ??
                                                        `#${goal.employee_id}`}
                                                </p>
                                            </div>
                                            <PermissionGate permission="can_manage_goals">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Label
                                                        htmlFor={`goal_progress_${goal.id}`}
                                                        className="sr-only"
                                                    >
                                                        {t('goal_progress')}
                                                    </Label>
                                                    <div className="flex items-center gap-1">
                                                        <Input
                                                            id={`goal_progress_${goal.id}`}
                                                            type="number"
                                                            min={0}
                                                            max={100}
                                                            className="h-8 w-16"
                                                            defaultValue={
                                                                goal.progress
                                                            }
                                                            key={`${goal.id}-${goal.progress}`}
                                                            disabled={
                                                                busy ||
                                                                cycle.status ===
                                                                    'finalized'
                                                            }
                                                            onBlur={(e) =>
                                                                void handleGoalProgressBlur(
                                                                    goal,
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <span className="text-xs text-muted-foreground">
                                                            %
                                                        </span>
                                                    </div>
                                                    <select
                                                        className="h-8 rounded-md border border-input bg-background px-2 text-sm"
                                                        value={goal.status}
                                                        disabled={
                                                            busy ||
                                                            cycle.status ===
                                                                'finalized'
                                                        }
                                                        onChange={(e) =>
                                                            void handleGoalStatusChange(
                                                                goal,
                                                                e.target
                                                                    .value as GoalStatus,
                                                            )
                                                        }
                                                    >
                                                        {GOAL_STATUSES.map(
                                                            (status) => (
                                                                <option
                                                                    key={status}
                                                                    value={
                                                                        status
                                                                    }
                                                                >
                                                                    {t(
                                                                        `goal_status.${status}`,
                                                                    )}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </div>
                                            </PermissionGate>
                                        </div>
                                        <ProgressMeter
                                            className="mt-3 max-w-md"
                                            value={goal.progress}
                                            label={t('goal_progress')}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeader
                            title={t('evaluations_title')}
                            count={evaluations.length}
                        />

                        <PermissionGate permission="can_evaluate_employee">
                            {cycle.status === 'active' ? (
                                unevaluatedParticipants.length === 0 ? (
                                    <div className="rounded-lg border border-border bg-muted/20 px-4 py-3 text-sm text-muted-foreground">
                                        {t('all_evaluated')}
                                    </div>
                                ) : (
                                    <form
                                        onSubmit={handleSubmitEvaluation}
                                        className="grid gap-3 rounded-lg border border-border bg-muted/10 p-4 sm:grid-cols-2"
                                    >
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="eval_employee">
                                                {t('employee_id')}
                                            </Label>
                                            <select
                                                id="eval_employee"
                                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                                value={evalEmployeeId ?? ''}
                                                onChange={(e) =>
                                                    setEvalEmployeeId(
                                                        e.target.value
                                                            ? Number(
                                                                  e.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="">
                                                    {t('select_participant')}
                                                </option>
                                                {unevaluatedParticipants.map(
                                                    (participant) => (
                                                        <option
                                                            key={
                                                                participant.employee_id
                                                            }
                                                            value={
                                                                participant.employee_id
                                                            }
                                                        >
                                                            {participantLabel(
                                                                participant,
                                                            )}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
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
                                                    setEvalSummary(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    'summary_placeholder',
                                                )}
                                            />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={
                                                    busy ||
                                                    evalEmployeeId === null
                                                }
                                            >
                                                {t('submit_evaluation')}
                                            </Button>
                                        </div>
                                    </form>
                                )
                            ) : null}
                        </PermissionGate>

                        {evaluations.length === 0 ? (
                            <EmptyState message={t('empty_evaluations')} />
                        ) : (
                            <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border">
                                {evaluations.map((evaluation) => (
                                    <li
                                        key={evaluation.id}
                                        className="flex flex-wrap items-center justify-between gap-3 px-3 py-3 text-sm"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {evaluation.employee_name ??
                                                    `#${evaluation.employee_id}`}
                                            </p>
                                            {evaluation.summary ? (
                                                <p className="text-muted-foreground">
                                                    {evaluation.summary}
                                                </p>
                                            ) : null}
                                            {evaluation.submitted_at ? (
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        evaluation.submitted_at,
                                                    ).toLocaleString(
                                                        i18n.language,
                                                    )}
                                                </p>
                                            ) : null}
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            className="text-sm tabular-nums"
                                        >
                                            {evaluation.overall_score}
                                            <span className="text-muted-foreground">
                                                /5
                                            </span>
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <PermissionGate permission="can_view_promotion_suggestions">
                        <section className="space-y-3">
                            <SectionHeader
                                title={t('promotions_title')}
                                count={suggestions.length}
                            />
                            {suggestions.length === 0 ? (
                                <EmptyState message={t('empty_promotions')} />
                            ) : (
                                <ul className="space-y-2">
                                    {suggestions.map((suggestion) => (
                                        <li
                                            key={suggestion.id}
                                            className={cn(
                                                'flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3 text-sm',
                                                suggestion.status ===
                                                    'acknowledged' &&
                                                    'bg-muted/20',
                                            )}
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {suggestion.employee_name ??
                                                        `#${suggestion.employee_id}`}
                                                </p>
                                                <div className="mt-1 flex flex-wrap items-center gap-2 text-muted-foreground">
                                                    <Badge variant="outline">
                                                        {suggestion.overall_score}
                                                        /5
                                                    </Badge>
                                                    <Badge
                                                        variant={
                                                            suggestion.status ===
                                                            'acknowledged'
                                                                ? 'secondary'
                                                                : 'default'
                                                        }
                                                    >
                                                        {t(
                                                            `promotion_status.${suggestion.status}`,
                                                            {
                                                                defaultValue:
                                                                    suggestion.status,
                                                            },
                                                        )}
                                                    </Badge>
                                                </div>
                                            </div>
                                            {suggestion.status !==
                                            'acknowledged' ? (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={busy}
                                                    onClick={() =>
                                                        void handleAcknowledge(
                                                            suggestion.id,
                                                        )
                                                    }
                                                >
                                                    {t('acknowledge')}
                                                </Button>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </PermissionGate>
                </div>
            )}

            <EmployeePickerDialog
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                onSelect={(employee) => void handleAddParticipant(employee)}
            />
        </AdminPageShell>
    );
}
