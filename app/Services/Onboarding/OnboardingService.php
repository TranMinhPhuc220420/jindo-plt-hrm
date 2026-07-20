<?php

namespace App\Services\Onboarding;

use App\Events\OnboardingCompleted;
use App\Events\OnboardingStarted;
use App\Events\OnboardingTaskCompleted;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\Offer;
use App\Models\OnboardingCase;
use App\Models\OnboardingTask;
use App\Models\OnboardingTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Employee\EmployeeAccountService;
use App\Services\Employee\EmployeeService;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    /**
     * Built-in checklist used when no template is supplied.
     *
     * @var list<array{key: string, title: string, mandatory: bool, assignee_type: string}>
     */
    public const DEFAULT_ITEMS = [
        ['key' => 'create_account', 'title' => 'Create user account', 'mandatory' => true, 'assignee_type' => 'hr'],
        ['key' => 'collect_documents', 'title' => 'Collect onboarding documents', 'mandatory' => true, 'assignee_type' => 'hr'],
        ['key' => 'assign_equipment', 'title' => 'Assign equipment', 'mandatory' => false, 'assignee_type' => 'it'],
        ['key' => 'orientation', 'title' => 'Orientation & training', 'mandatory' => false, 'assignee_type' => 'hr'],
        ['key' => 'probation_review', 'title' => 'Probation review', 'mandatory' => false, 'assignee_type' => 'manager'],
    ];

    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly EmployeeService $employees,
        private readonly EmployeeAccountService $accounts,
    ) {}

    /**
     * @param  array{status?: string, employee_id?: int}  $filters
     * @return LengthAwarePaginator<int, OnboardingCase>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = OnboardingCase::query()
            ->where('company_id', $this->companyContext->id())
            ->with('tasks')
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): OnboardingCase
    {
        $case = OnboardingCase::query()
            ->where('company_id', $this->companyContext->id())
            ->with('tasks')
            ->find($id);

        if ($case === null) {
            throw new DomainException(
                message: 'Onboarding case not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $case;
    }

    public function findTask(int $id): OnboardingTask
    {
        $task = OnboardingTask::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($task === null) {
            throw new DomainException(
                message: 'Onboarding task not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $task;
    }

    public function startFromOffer(Offer $offer, Employee $employee, ?int $templateId = null): OnboardingCase
    {
        return $this->startCase([
            'employee_id' => $employee->id,
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
            'onboarding_template_id' => $templateId,
            'probation_ends_on' => $offer->probation_ends_on?->toDateString(),
        ]);
    }

    /**
     * @param  array{employee_id: int, template_id?: int|null, offer_id?: int|null, probation_ends_on?: string|null}  $data
     */
    public function startManual(array $data): OnboardingCase
    {
        $companyId = $this->companyContext->id();

        $employee = Employee::query()
            ->where('company_id', $companyId)
            ->find($data['employee_id']);

        if ($employee === null) {
            throw new DomainException(
                message: 'Employee is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }

        return $this->startCase([
            'employee_id' => $employee->id,
            'offer_id' => $data['offer_id'] ?? null,
            'candidate_id' => null,
            'onboarding_template_id' => $data['template_id'] ?? null,
            'probation_ends_on' => $data['probation_ends_on'] ?? null,
        ]);
    }

    /**
     * @param  array{employee_id: int, offer_id: ?int, candidate_id: ?int, onboarding_template_id: ?int, probation_ends_on: ?string}  $attributes
     */
    private function startCase(array $attributes): OnboardingCase
    {
        $companyId = $this->companyContext->id();
        $items = $this->resolveItems($companyId, $attributes['onboarding_template_id']);

        return DB::transaction(function () use ($companyId, $attributes, $items): OnboardingCase {
            $case = OnboardingCase::query()->create([
                'company_id' => $companyId,
                'employee_id' => $attributes['employee_id'],
                'offer_id' => $attributes['offer_id'],
                'candidate_id' => $attributes['candidate_id'],
                'onboarding_template_id' => $attributes['onboarding_template_id'],
                'status' => 'in_progress',
                'probation_ends_on' => $attributes['probation_ends_on'],
                'started_at' => now(),
            ]);

            foreach ($items as $index => $item) {
                $case->tasks()->create([
                    'company_id' => $companyId,
                    'key' => $item['key'],
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'mandatory' => $item['mandatory'],
                    'assignee_type' => $item['assignee_type'],
                    'status' => 'pending',
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }

            $this->audit->write(
                action: 'onboarding.started',
                subject: $case,
                payload: ['employee_id' => $case->employee_id, 'task_count' => count($items)],
            );

            OnboardingStarted::dispatch($case);

            return $case->fresh('tasks');
        });
    }

    /**
     * @return list<array{key: string, title: string, description?: string|null, mandatory: bool, assignee_type: string, sort_order?: int}>
     */
    private function resolveItems(int $companyId, ?int $templateId): array
    {
        $template = null;

        if ($templateId !== null) {
            $template = OnboardingTemplate::query()
                ->where('company_id', $companyId)
                ->with('items')
                ->find($templateId);

            if ($template === null) {
                throw new DomainException(
                    message: 'Onboarding template is outside the current company scope.',
                    errorCode: 'COMPANY_SCOPE_MISMATCH',
                    status: 404,
                );
            }
        }

        if ($template !== null && $template->items->isNotEmpty()) {
            return $template->items
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($item) => [
                    'key' => $item->key,
                    'title' => $item->title,
                    'description' => $item->description,
                    'mandatory' => (bool) $item->mandatory,
                    'assignee_type' => $item->assignee_type,
                    'sort_order' => $item->sort_order,
                ])
                ->all();
        }

        return self::DEFAULT_ITEMS;
    }

    public function completeTask(OnboardingTask $task, User $actor): OnboardingTask
    {
        $this->assertCompanyScope($task->company_id);

        $case = $task->onboardingCase;

        if ($case->status !== 'in_progress') {
            throw new DomainException(
                message: 'Cannot complete tasks on a case that is not in progress.',
                errorCode: 'ONBOARDING_ALREADY_COMPLETED',
                status: 422,
            );
        }

        return DB::transaction(function () use ($task, $actor): OnboardingTask {
            if ($task->key === 'create_account') {
                $this->provisionAccount($task);
            }

            $task->status = 'done';
            $task->completed_at = now();
            $task->completed_by = $actor->id;
            $task->save();

            $this->audit->write(
                action: 'onboarding.task_completed',
                subject: $task,
                payload: ['key' => $task->key, 'onboarding_case_id' => $task->onboarding_case_id],
            );

            OnboardingTaskCompleted::dispatch($task);

            return $task->fresh();
        });
    }

    public function reopenTask(OnboardingTask $task): OnboardingTask
    {
        $this->assertCompanyScope($task->company_id);

        $task->status = 'pending';
        $task->completed_at = null;
        $task->completed_by = null;
        $task->save();

        $this->audit->write(
            action: 'onboarding.task_reopened',
            subject: $task,
            payload: ['key' => $task->key, 'onboarding_case_id' => $task->onboarding_case_id],
        );

        return $task->fresh();
    }

    public function completeCase(OnboardingCase $case, User $actor): OnboardingCase
    {
        $this->assertCompanyScope($case->company_id);

        if ($case->status === 'completed') {
            throw new DomainException(
                message: 'Onboarding case is already completed.',
                errorCode: 'ONBOARDING_ALREADY_COMPLETED',
                status: 422,
            );
        }

        $mandatoryPending = $case->tasks()
            ->where('mandatory', true)
            ->where('status', '!=', 'done')
            ->count();

        if ($mandatoryPending > 0) {
            throw new DomainException(
                message: 'Complete all mandatory onboarding tasks before finishing the case.',
                errorCode: 'ONBOARDING_MANDATORY_PENDING',
                status: 422,
            );
        }

        return DB::transaction(function () use ($case): OnboardingCase {
            $case->status = 'completed';
            $case->completed_at = now();
            $case->save();

            $employee = Employee::query()
                ->where('company_id', $case->company_id)
                ->find($case->employee_id);

            if ($employee !== null && $employee->status === 'probation') {
                $this->employees->changeStatus($employee, [
                    'status' => 'active',
                    'reason' => 'Onboarding completed',
                ]);
            }

            $this->audit->write(
                action: 'onboarding.completed',
                subject: $case,
                payload: ['employee_id' => $case->employee_id],
            );

            OnboardingCompleted::dispatch($case);

            return $case->fresh('tasks');
        });
    }

    /**
     * @return LengthAwarePaginator<int, OnboardingTemplate>
     */
    public function listTemplates(int $perPage = 20): LengthAwarePaginator
    {
        return OnboardingTemplate::query()
            ->where('company_id', $this->companyContext->id())
            ->with('items')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findTemplate(int $id): OnboardingTemplate
    {
        $template = OnboardingTemplate::query()
            ->where('company_id', $this->companyContext->id())
            ->with('items')
            ->find($id);

        if ($template === null) {
            throw new DomainException(
                message: 'Onboarding template not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $template;
    }

    /**
     * @param  array{name: string, description?: string|null, is_active?: bool, items?: list<array<string, mixed>>}  $data
     */
    public function createTemplate(array $data): OnboardingTemplate
    {
        $companyId = $this->companyContext->id();

        return DB::transaction(function () use ($companyId, $data): OnboardingTemplate {
            $template = OnboardingTemplate::query()->create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncTemplateItems($template, $data['items'] ?? []);

            $this->audit->write(
                action: 'onboarding.template_created',
                subject: $template,
                payload: ['name' => $template->name],
            );

            return $template->fresh('items');
        });
    }

    /**
     * @param  array{name?: string, description?: string|null, is_active?: bool, items?: list<array<string, mixed>>}  $data
     */
    public function updateTemplate(OnboardingTemplate $template, array $data): OnboardingTemplate
    {
        $this->assertCompanyScope($template->company_id);

        return DB::transaction(function () use ($template, $data): OnboardingTemplate {
            $template->fill(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ], fn ($value) => $value !== null));

            if (array_key_exists('is_active', $data)) {
                $template->is_active = $data['is_active'];
            }

            $template->save();

            if (array_key_exists('items', $data)) {
                $template->items()->delete();
                $this->syncTemplateItems($template, $data['items']);
            }

            $this->audit->write(
                action: 'onboarding.template_updated',
                subject: $template,
                payload: ['name' => $template->name],
            );

            return $template->fresh('items');
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncTemplateItems(OnboardingTemplate $template, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $template->items()->create([
                'company_id' => $template->company_id,
                'key' => $item['key'],
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'mandatory' => $item['mandatory'] ?? false,
                'assignee_type' => $item['assignee_type'] ?? 'hr',
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    private function provisionAccount(OnboardingTask $task): void
    {
        $employee = Employee::query()
            ->where('company_id', $task->company_id)
            ->find($task->onboardingCase->employee_id);

        if ($employee === null) {
            return;
        }

        $this->accounts->provisionForEmployee($employee);
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }
}
