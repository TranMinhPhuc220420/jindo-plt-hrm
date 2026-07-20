<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Support\Locale\LocaleResolver;
use App\Support\Locale\SupportedLocales;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class AuthService
{
    public function __construct(
        private readonly LocaleResolver $localeResolver,
    ) {}

    /**
     * Authenticate for the SPA session (Sanctum cookie).
     *
     * @return array<string, mixed>
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $this->ensureIsNotRateLimited($email);

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey($email));

            throw new DomainException(
                message: 'Invalid credentials.',
                errorCode: 'AUTH_INVALID_CREDENTIALS',
                status: 401,
            );
        }

        RateLimiter::clear($this->throttleKey($email));

        if ($this->requiresTwoFactorChallenge($user)) {
            session([
                'login.id' => $user->id,
                'login.remember' => $remember,
            ]);

            return [
                'two_factor_required' => true,
                'challenge_token' => (string) session()->getId(),
            ];
        }

        Auth::login($user, $remember);
        request()->session()->regenerate();

        return $this->authPayload($user);
    }

    /**
     * Complete a pending two-factor challenge from login.
     *
     * @return array<string, mixed>
     */
    public function challengeTwoFactor(string $code, bool $recovery = false): array
    {
        $userId = session('login.id');

        if (! $userId) {
            throw new DomainException(
                message: 'Two-factor authentication challenge is required.',
                errorCode: 'AUTH_TWO_FACTOR_REQUIRED',
                status: 422,
            );
        }

        $email = (string) ($userId.'|challenge');
        $this->ensureIsNotRateLimited($email);

        $user = User::query()->find($userId);

        if (! $user instanceof User || ! $this->requiresTwoFactorChallenge($user)) {
            session()->forget(['login.id', 'login.remember']);

            throw new DomainException(
                message: 'Two-factor authentication challenge is required.',
                errorCode: 'AUTH_TWO_FACTOR_REQUIRED',
                status: 422,
            );
        }

        $valid = $recovery
            ? $this->verifyRecoveryCode($user, $code)
            : $this->verifyTotpCode($user, $code);

        if (! $valid) {
            RateLimiter::hit($this->throttleKey($email));

            throw new DomainException(
                message: 'Invalid two-factor authentication code.',
                errorCode: 'AUTH_TWO_FACTOR_INVALID',
                status: 422,
            );
        }

        RateLimiter::clear($this->throttleKey($email));

        $remember = (bool) session('login.remember', false);
        session()->forget(['login.id', 'login.remember']);

        Auth::login($user, $remember);
        request()->session()->regenerate();

        $fresh = $user->fresh();
        if (! $fresh instanceof User) {
            throw new DomainException(
                message: 'Authenticated user could not be refreshed.',
                errorCode: 'AUTH_USER_NOT_FOUND',
                status: 500,
            );
        }

        return $this->authPayload($fresh);
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        Auth::forgetGuards();
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        return $this->authPayload($user);
    }

    /**
     * Request a password reset link. Always succeeds from the caller's perspective
     * to avoid account enumeration.
     */
    public function forgotPassword(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function resetPassword(array $credentials): void
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new DomainException(
                message: 'Invalid or expired reset token.',
                errorCode: 'AUTH_RESET_TOKEN_INVALID',
                status: 422,
            );
        }
    }

    /**
     * Persist personal locale preference (`null` clears → follow company).
     *
     * @return array<string, mixed>
     */
    public function updateLocale(User $user, ?string $locale): array
    {
        if ($locale !== null && ! SupportedLocales::isValid($locale)) {
            throw new DomainException(
                message: 'Invalid locale.',
                errorCode: 'LOCALE_INVALID',
                status: 422,
            );
        }

        $user->forceFill(['locale' => $locale])->save();

        app()->setLocale($this->localeResolver->resolve($user->fresh()));

        $fresh = $user->fresh();
        if (! $fresh instanceof User) {
            throw new DomainException(
                message: 'Authenticated user could not be refreshed.',
                errorCode: 'AUTH_USER_NOT_FOUND',
                status: 500,
            );
        }

        return $this->authPayload($fresh);
    }

    /**
     * Identity payload for login /me.
     *
     * @return array<string, mixed>
     */
    public function authPayload(User $user): array
    {
        $user->loadMissing('employee');

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'permissions' => $user->permissionKeys(),
            'employee_id' => $user->employee?->id,
            'two_factor_required' => false,
            ...$this->localeResolver->payload($user),
        ];
    }

    protected function requiresTwoFactorChallenge(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    protected function verifyTotpCode(User $user, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        try {
            return app(TwoFactorAuthenticationProvider::class)->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $code,
            );
        } catch (\Throwable) {
            return false;
        }
    }

    protected function verifyRecoveryCode(User $user, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        $match = collect($user->recoveryCodes())->first(
            fn (string $recoveryCode) => hash_equals($recoveryCode, $code),
        );

        if ($match === null) {
            return false;
        }

        $user->replaceRecoveryCode($match);

        return true;
    }

    protected function ensureIsNotRateLimited(string $email): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($email));

        throw new DomainException(
            message: 'Too many login attempts. Please try again later.',
            errorCode: 'TOO_MANY_REQUESTS',
            status: 429,
            meta: ['retry_after' => $seconds],
        );
    }

    protected function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.request()->ip());
    }
}
