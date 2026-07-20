<?php

namespace App\Services\Notification;

use App\Exceptions\DomainException;
use App\Jobs\SendNotificationEmailJob;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Organization\CompanyContext;
use App\Support\Locale\LocaleResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly LocaleResolver $locales,
    ) {}

    /**
     * Create an in-app notification and, when the user opts in, queue an email.
     *
     * When `$title` / `$body` are omitted, copy is resolved from
     * `lang/{locale}/notifications.php` using the recipient's effective locale
     * and the dotted `$type` key (e.g. `leave.approved`).
     *
     * @param  array<string, mixed>  $data
     */
    public function notify(
        User $user,
        string $type,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        ?int $companyId = null,
    ): Notification {
        try {
            $companyId ??= $this->companyContext->id();
        } catch (\Throwable) {
            $companyId = null;
        }

        $prefs = $this->preferencesFor($user);
        $category = str_contains($type, '.') ? explode('.', $type)[0] : $type;
        [$resolvedTitle, $resolvedBody] = $this->resolveCopy($user, $type, $title, $body);

        $notification = Notification::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $resolvedTitle,
            'body' => $resolvedBody,
            'data' => $data === [] ? null : $data,
            'read_at' => null,
        ]);

        if ($this->wantsEmail($prefs, $category)) {
            SendNotificationEmailJob::dispatch($notification->id);
        }

        return $notification;
    }

    /**
     * Resolve the user linked to an employee, then notify them if present.
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyEmployee(
        ?int $employeeId,
        string $type,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        ?int $companyId = null,
    ): ?Notification {
        if ($employeeId === null) {
            return null;
        }

        $employee = Employee::query()->find($employeeId);
        $user = $employee?->user;

        if ($user === null) {
            return null;
        }

        return $this->notify($user, $type, $title, $body, $data, $companyId ?? $employee?->company_id);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolveCopy(
        User $user,
        string $type,
        ?string $title,
        ?string $body,
    ): array {
        $locale = $this->locales->resolve($user);
        $titleKey = "notifications.{$type}.title";
        $bodyKey = "notifications.{$type}.body";

        $resolvedTitle = $title ?? __($titleKey, [], $locale);
        if ($resolvedTitle === $titleKey) {
            $resolvedTitle = $title ?? $type;
        }

        $resolvedBody = $body;
        if ($resolvedBody === null) {
            $translated = __($bodyKey, [], $locale);
            $resolvedBody = $translated === $bodyKey ? null : $translated;
        }

        return [$resolvedTitle, $resolvedBody];
    }

    /**
     * @param  array{unread_only?: bool|int|string, type?: string}  $filters
     * @return LengthAwarePaginator<int, Notification>
     */
    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id');

        if (! empty($filters['unread_only'])) {
            $query->whereNull('read_at');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, int $id): Notification
    {
        $notification = $this->findOwned($user, $id);

        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(User $user, int $id): void
    {
        $notification = $this->findOwned($user, $id);
        $notification->delete();
    }

    public function preferencesFor(User $user): NotificationPreference
    {
        return NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['email' => true, 'push' => false, 'system' => true, 'categories' => null],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePreferences(User $user, array $data): NotificationPreference
    {
        $prefs = $this->preferencesFor($user);

        $prefs->fill([
            'email' => (bool) ($data['email'] ?? $prefs->email),
            'push' => (bool) ($data['push'] ?? $prefs->push),
            'system' => (bool) ($data['system'] ?? $prefs->system),
            'categories' => $data['categories'] ?? $prefs->categories,
        ]);
        $prefs->save();

        return $prefs;
    }

    private function wantsEmail(NotificationPreference $prefs, string $category): bool
    {
        $categories = $prefs->categories ?? [];

        if (is_array($categories) && array_key_exists($category, $categories)) {
            $override = $categories[$category];
            if (is_array($override) && array_key_exists('email', $override)) {
                return (bool) $override['email'];
            }
        }

        return (bool) $prefs->email;
    }

    /**
     * Broadcast an announcement to all users linked to employees in the company.
     *
     * @return int Number of inbox rows created
     */
    public function broadcast(
        string $title,
        ?string $body = null,
        ?int $companyId = null,
    ): int {
        $companyId ??= $this->companyContext->id();

        $userIds = User::query()
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->pluck('id');

        $count = 0;
        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);
            if ($user === null) {
                continue;
            }

            $this->notify(
                user: $user,
                type: 'broadcast.announcement',
                title: $title,
                body: $body,
                data: ['broadcast' => true],
                companyId: $companyId,
            );
            $count++;
        }

        return $count;
    }

    private function findOwned(User $user, int $id): Notification
    {
        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->find($id);

        if ($notification === null) {
            throw new DomainException(
                message: 'Notification not found.',
                errorCode: 'NOTIFICATION_NOT_FOUND',
                status: 404,
            );
        }

        return $notification;
    }
}
