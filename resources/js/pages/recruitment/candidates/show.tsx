import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import {
    CurrencyInput,
    CurrencySelect,
} from '@/components/shared/currency-input';
import { DateTimePicker } from '@/components/shared/date-time-picker';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as recruitmentApi from '@/lib/api/modules/recruitment';
import type {
    Candidate,
    CandidateStage,
    Interview,
    Offer,
} from '@/lib/api/modules/recruitment';
import { loadCompanyCurrency } from '@/lib/company-currency';
import { formatCurrency, parseMoneyInput } from '@/lib/currency';
import type { AppCurrency } from '@/lib/currency';
import { toApiDateTime } from '@/lib/datetime';

type Props = {
    id: number;
};

const STAGES: CandidateStage[] = [
    'applied',
    'screening',
    'interview',
    'offer',
    'hired',
    'rejected',
    'withdrawn',
];

export default function CandidateShowPage({ id }: Props) {
    const { t } = useTranslation(['recruitment', 'common']);
    const [candidate, setCandidate] = useState<Candidate | null>(null);
    const [interviews, setInterviews] = useState<Interview[]>([]);
    const [offers, setOffers] = useState<Offer[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [stage, setStage] = useState<CandidateStage>('applied');
    const [interviewMode, setInterviewMode] = useState('');
    const [interviewAt, setInterviewAt] = useState('');
    const [offerTitle, setOfferTitle] = useState('');
    const [offerSalary, setOfferSalary] = useState('');
    const [offerCurrency, setOfferCurrency] = useState<AppCurrency>('VND');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const candidateData = await recruitmentApi.getCandidate(id);
            setCandidate(candidateData ?? null);

            if (candidateData) {
                setStage(candidateData.stage);
            }

            const [interviewList, offerList, companyCurrency] =
                await Promise.all([
                    recruitmentApi.listInterviews(id).catch(() => []),
                    recruitmentApi.listOffers(id).catch(() => []),
                    loadCompanyCurrency(),
                ]);
            setInterviews(interviewList);
            setOffers(offerList);
            setOfferCurrency(companyCurrency);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('candidate.error_load'),
            );
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
                err instanceof ApiError
                    ? err.message
                    : t('candidate.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleStage(event: FormEvent) {
        event.preventDefault();
        await withBusy(async () => {
            await recruitmentApi.changeCandidateStage(id, stage);
            toast.success(t('candidate.toast_stage'));
            await load();
        });
    }

    async function handleScheduleInterview(event: FormEvent) {
        event.preventDefault();
        await withBusy(async () => {
            await recruitmentApi.scheduleInterview(id, {
                mode: interviewMode || undefined,
                scheduled_at: toApiDateTime(interviewAt) ?? undefined,
            });
            toast.success(t('candidate.toast_interview'));
            setInterviewMode('');
            setInterviewAt('');
            await load();
        });
    }

    async function handleCreateOffer(event: FormEvent) {
        event.preventDefault();
        await withBusy(async () => {
            const salary = parseMoneyInput(offerSalary, offerCurrency);
            await recruitmentApi.createOffer(id, {
                title: offerTitle || undefined,
                salary_amount: salary ?? undefined,
                currency: offerCurrency,
            });
            toast.success(t('candidate.toast_offer_created'));
            setOfferTitle('');
            setOfferSalary('');
            await load();
        });
    }

    async function handleOfferAction(
        offerId: number,
        action: 'send' | 'accept' | 'reject',
    ) {
        await withBusy(async () => {
            if (action === 'send') {
                await recruitmentApi.sendOffer(offerId);
            } else if (action === 'accept') {
                await recruitmentApi.acceptOffer(offerId);
            } else {
                await recruitmentApi.rejectOffer(offerId);
            }

            toast.success(t(`candidate.toast_offer_${action}`));
            await load();
        });
    }

    return (
        <AdminPageShell
            title={candidate?.full_name ?? t('candidate.title')}
            description={t('candidate.description')}
            any={['can_view_candidates', 'can_manage_candidates']}
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/recruitment">{t('candidate.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('candidate.loading')} />
            ) : error || !candidate ? (
                <ErrorState message={error ?? t('candidate.error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-muted-foreground">
                                {t('candidate.email')}
                            </p>
                            <p>{candidate.email ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('candidate.phone')}
                            </p>
                            <p>{candidate.phone ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('candidate.stage_label')}
                            </p>
                            <p>
                                {t(`stage.${candidate.stage}`, {
                                    defaultValue: candidate.stage,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('candidate.source')}
                            </p>
                            <p>{candidate.source ?? '—'}</p>
                        </div>
                    </div>

                    <PermissionGate permission="can_manage_candidates">
                        <form
                            onSubmit={handleStage}
                            className="grid max-w-md gap-3 border-t border-border pt-6"
                        >
                            <h2 className="text-sm font-medium">
                                {t('candidate.move_stage')}
                            </h2>
                            <select
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                value={stage}
                                onChange={(e) =>
                                    setStage(e.target.value as CandidateStage)
                                }
                            >
                                {STAGES.map((s) => (
                                    <option key={s} value={s}>
                                        {t(`stage.${s}`)}
                                    </option>
                                ))}
                            </select>
                            <Button type="submit" disabled={busy}>
                                {t('candidate.update_stage')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <section className="border-t border-border pt-6">
                        <h2 className="mb-3 text-sm font-medium">
                            {t('candidate.interviews_title')}
                        </h2>

                        <PermissionGate permission="can_manage_interviews">
                            <form
                                onSubmit={handleScheduleInterview}
                                className="mb-4 grid max-w-xl gap-3"
                            >
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="interview_mode">
                                            {t('candidate.mode')}
                                        </Label>
                                        <Input
                                            id="interview_mode"
                                            value={interviewMode}
                                            onChange={(e) =>
                                                setInterviewMode(e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="interview_at">
                                            {t('candidate.scheduled_at')}
                                        </Label>
                                        <DateTimePicker
                                            id="interview_at"
                                            value={interviewAt}
                                            onChange={setInterviewAt}
                                        />
                                    </div>
                                </div>
                                <Button type="submit" disabled={busy}>
                                    {t('candidate.schedule_interview')}
                                </Button>
                            </form>
                        </PermissionGate>

                        {interviews.length === 0 ? (
                            <EmptyState
                                message={t('candidate.empty_interviews')}
                            />
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {interviews.map((interview) => (
                                    <li
                                        key={interview.id}
                                        className="flex justify-between border-b border-border/60 pb-2"
                                    >
                                        <span>
                                            {interview.mode ??
                                                t('candidate.interview')}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {interview.scheduled_at ?? '—'} ·{' '}
                                            {interview.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="border-t border-border pt-6">
                        <h2 className="mb-3 text-sm font-medium">
                            {t('candidate.offers_title')}
                        </h2>

                        <PermissionGate permission="can_create_offer">
                            <form
                                onSubmit={handleCreateOffer}
                                className="mb-4 grid max-w-xl gap-3"
                            >
                                <div className="grid gap-2 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="offer_title">
                                            {t('candidate.offer_title_label')}
                                        </Label>
                                        <Input
                                            id="offer_title"
                                            value={offerTitle}
                                            onChange={(e) =>
                                                setOfferTitle(e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="offer_salary">
                                            {t('candidate.salary')}
                                        </Label>
                                        <CurrencyInput
                                            id="offer_salary"
                                            value={offerSalary}
                                            currency={offerCurrency}
                                            onChange={setOfferSalary}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="offer_currency">
                                            {t('candidate.currency')}
                                        </Label>
                                        <CurrencySelect
                                            id="offer_currency"
                                            value={offerCurrency}
                                            onChange={(next) => {
                                                setOfferCurrency(next);
                                                const n = parseMoneyInput(
                                                    offerSalary,
                                                    next,
                                                );
                                                setOfferSalary(
                                                    n === null ? '' : String(n),
                                                );
                                            }}
                                        />
                                    </div>
                                </div>
                                <Button type="submit" disabled={busy}>
                                    {t('candidate.create_offer')}
                                </Button>
                            </form>
                        </PermissionGate>

                        {offers.length === 0 ? (
                            <EmptyState message={t('candidate.empty_offers')} />
                        ) : (
                            <div className="space-y-3">
                                {offers.map((offer) => (
                                    <div
                                        key={offer.id}
                                        className="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-3 text-sm"
                                    >
                                        <div>
                                            <p>
                                                {offer.title ??
                                                    t('candidate.offer')}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {formatCurrency(
                                                    offer.salary_amount,
                                                    offer.currency,
                                                )}{' '}
                                                · {offer.status}
                                            </p>
                                        </div>
                                        <div className="flex gap-2">
                                            <PermissionGate
                                                all={[
                                                    'can_create_offer',
                                                    'can_approve_offer',
                                                ]}
                                            >
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={
                                                        busy ||
                                                        offer.status !== 'draft'
                                                    }
                                                    onClick={() =>
                                                        void handleOfferAction(
                                                            offer.id,
                                                            'send',
                                                        )
                                                    }
                                                >
                                                    {t('candidate.send_offer')}
                                                </Button>
                                            </PermissionGate>
                                            <PermissionGate permission="can_hire_candidate">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    disabled={
                                                        busy ||
                                                        offer.status !== 'sent'
                                                    }
                                                    onClick={() =>
                                                        void handleOfferAction(
                                                            offer.id,
                                                            'accept',
                                                        )
                                                    }
                                                >
                                                    {t(
                                                        'candidate.accept_offer',
                                                    )}
                                                </Button>
                                            </PermissionGate>
                                            <PermissionGate
                                                any={[
                                                    'can_create_offer',
                                                    'can_approve_offer',
                                                ]}
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={
                                                        busy ||
                                                        offer.status ===
                                                            'accepted' ||
                                                        offer.status ===
                                                            'rejected'
                                                    }
                                                    onClick={() =>
                                                        void handleOfferAction(
                                                            offer.id,
                                                            'reject',
                                                        )
                                                    }
                                                >
                                                    {t(
                                                        'candidate.reject_offer',
                                                    )}
                                                </Button>
                                            </PermissionGate>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            )}
        </AdminPageShell>
    );
}
