import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
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
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as recruitmentApi from '@/lib/api/modules/recruitment';
import type { Candidate, JobOpening } from '@/lib/api/modules/recruitment';

export default function RecruitmentIndexPage() {
    const { t } = useTranslation(['recruitment', 'common']);
    const [openings, setOpenings] = useState<JobOpening[]>([]);
    const [candidates, setCandidates] = useState<Candidate[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [openingOpen, setOpeningOpen] = useState(false);
    const [jobTitle, setJobTitle] = useState('');
    const [jobHeadcount, setJobHeadcount] = useState('');

    const [candidateOpen, setCandidateOpen] = useState(false);
    const [candidateName, setCandidateName] = useState('');
    const [candidateEmail, setCandidateEmail] = useState('');
    const [candidateJobId, setCandidateJobId] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [openingsResult, candidatesResult] = await Promise.all([
                recruitmentApi.listJobOpenings({ per_page: 50 }),
                recruitmentApi.listCandidates({ per_page: 50 }),
            ]);
            setOpenings(openingsResult.data);
            setCandidates(candidatesResult.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useLoadEffect(load, [load]);

    function handleOpeningOpenChange(open: boolean) {
        setOpeningOpen(open);

        if (!open) {
            setJobTitle('');
            setJobHeadcount('');
        }
    }

    function handleCandidateOpenChange(open: boolean) {
        setCandidateOpen(open);

        if (!open) {
            setCandidateName('');
            setCandidateEmail('');
            setCandidateJobId('');
        }
    }

    async function handleCreateOpening(event: FormEvent) {
        event.preventDefault();
        setBusy(true);

        try {
            await recruitmentApi.createJobOpening({
                title: jobTitle,
                headcount: jobHeadcount ? Number(jobHeadcount) : undefined,
            });
            toast.success(t('index.toast_opening_created'));
            handleOpeningOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleCreateCandidate(event: FormEvent) {
        event.preventDefault();
        setBusy(true);

        try {
            await recruitmentApi.createCandidate({
                job_opening_id: Number(candidateJobId),
                full_name: candidateName,
                email: candidateEmail || undefined,
            });
            toast.success(t('index.toast_candidate_created'));
            handleCandidateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            any={[
                'can_view_candidates',
                'can_manage_candidates',
                'can_manage_job_positions',
            ]}
            actions={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <PermissionGate permission="can_manage_job_positions">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpeningOpen(true)}
                        >
                            {t('index.create_opening')}
                        </Button>
                    </PermissionGate>
                    <PermissionGate permission="can_manage_candidates">
                        <Button
                            type="button"
                            onClick={() => setCandidateOpen(true)}
                        >
                            {t('index.create_candidate')}
                        </Button>
                    </PermissionGate>
                </div>
            }
        >
            <Dialog open={openingOpen} onOpenChange={handleOpeningOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('index.create_opening')}</DialogTitle>
                        <DialogDescription>
                            {t('index.openings_title')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreateOpening} className="grid gap-4">
                        <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                            <div className="grid gap-2">
                                <Label htmlFor="job_title">
                                    {t('index.opening_title_label')}
                                </Label>
                                <Input
                                    id="job_title"
                                    value={jobTitle}
                                    onChange={(e) =>
                                        setJobTitle(e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="job_headcount">
                                    {t('index.headcount')}
                                </Label>
                                <Input
                                    id="job_headcount"
                                    type="number"
                                    min={1}
                                    value={jobHeadcount}
                                    onChange={(e) =>
                                        setJobHeadcount(e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={busy}>
                                {t('index.create_opening')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={candidateOpen}
                onOpenChange={handleCandidateOpenChange}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('index.create_candidate')}</DialogTitle>
                        <DialogDescription>
                            {t('index.candidates_title')}
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        onSubmit={handleCreateCandidate}
                        className="grid gap-4"
                    >
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="candidate_name">
                                    {t('index.candidate_name')}
                                </Label>
                                <Input
                                    id="candidate_name"
                                    value={candidateName}
                                    onChange={(e) =>
                                        setCandidateName(e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="candidate_job">
                                    {t('index.candidate_job')}
                                </Label>
                                <select
                                    id="candidate_job"
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    value={candidateJobId}
                                    onChange={(e) =>
                                        setCandidateJobId(e.target.value)
                                    }
                                    required
                                >
                                    <option value="">
                                        {t('index.select_job')}
                                    </option>
                                    {openings.map((opening) => (
                                        <option
                                            key={opening.id}
                                            value={opening.id}
                                        >
                                            {opening.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="candidate_email">
                                {t('index.candidate_email')}
                            </Label>
                            <Input
                                id="candidate_email"
                                type="email"
                                value={candidateEmail}
                                onChange={(e) =>
                                    setCandidateEmail(e.target.value)
                                }
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={busy}>
                                {t('index.create_candidate')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : (
                <div className="space-y-10">
                    <section className="space-y-4">
                        <h2 className="text-sm font-medium">
                            {t('index.openings_title')}
                        </h2>

                        {openings.length === 0 ? (
                            <EmptyState message={t('index.empty_openings')} />
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">
                                                {t('index.col_title')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('index.col_headcount')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('index.col_status')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {openings.map((opening) => (
                                            <tr
                                                key={opening.id}
                                                className="border-t border-border/60"
                                            >
                                                <td className="px-3 py-3">
                                                    {opening.title}
                                                </td>
                                                <td className="px-3 py-3">
                                                    {opening.headcount ?? '—'}
                                                </td>
                                                <td className="px-3 py-3">
                                                    {t(
                                                        `job_status.${opening.status}`,
                                                        {
                                                            defaultValue:
                                                                opening.status,
                                                        },
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-sm font-medium">
                            {t('index.candidates_title')}
                        </h2>

                        {candidates.length === 0 ? (
                            <EmptyState message={t('index.empty_candidates')} />
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">
                                                {t('index.col_name')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('index.col_stage')}
                                            </th>
                                            <th className="px-3 py-2 font-medium" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {candidates.map((candidate) => (
                                            <tr
                                                key={candidate.id}
                                                className="border-t border-border/60"
                                            >
                                                <td className="px-3 py-3">
                                                    {candidate.full_name}
                                                </td>
                                                <td className="px-3 py-3">
                                                    {t(
                                                        `stage.${candidate.stage}`,
                                                        {
                                                            defaultValue:
                                                                candidate.stage,
                                                        },
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-right">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/recruitment/candidates/${candidate.id}`}
                                                        >
                                                            {t('index.open')}
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            )}
        </AdminPageShell>
    );
}
