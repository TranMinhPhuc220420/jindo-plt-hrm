import { Head, router } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { ApiError } from '@/lib/api/errors';
import {
    challengeTwoFactor,
    login as loginRequest,
} from '@/lib/api/modules/auth';
import { useAuth } from '@/lib/auth/auth-context';
import i18n from '@/lib/i18n';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    const { t } = useTranslation(['auth', 'common']);
    const { setSession } = useAuth();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [formError, setFormError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

    const [awaitingTwoFactor, setAwaitingTwoFactor] = useState(false);
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);
    const [otpCode, setOtpCode] = useState('');
    const [recoveryCode, setRecoveryCode] = useState('');

    const challengeCopy = useMemo(() => {
        if (showRecoveryInput) {
            return {
                title: t('two_factor.recovery_title'),
                description: t('two_factor.recovery_description'),
                toggleText: t('two_factor.toggle_to_code'),
            };
        }

        return {
            title: t('two_factor.auth_code_title'),
            description: t('two_factor.auth_code_description'),
            toggleText: t('two_factor.toggle_to_recovery'),
        };
    }, [showRecoveryInput, t]);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setProcessing(true);
        setFormError(null);
        setFieldErrors({});

        try {
            const data = await loginRequest({
                email,
                password,
                remember,
            });

            if ('challenge_token' in data && data.two_factor_required) {
                setAwaitingTwoFactor(true);
                setProcessing(false);

                return;
            }

            if ('user' in data) {
                setSession(data);
                router.visit('/dashboard');
            }
        } catch (error) {
            if (error instanceof ApiError) {
                setFormError(error.message);
                setFieldErrors(error.fieldErrors);
            } else {
                setFormError(t('login.error_generic'));
            }

            setProcessing(false);
        }
    }

    async function handleChallenge(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setProcessing(true);
        setFormError(null);
        setFieldErrors({});

        try {
            const data = await challengeTwoFactor(
                showRecoveryInput
                    ? { recovery_code: recoveryCode }
                    : { code: otpCode },
            );
            setSession(data);
            router.visit('/dashboard');
        } catch (error) {
            if (error instanceof ApiError) {
                setFormError(error.message);
                setFieldErrors(error.fieldErrors);
            } else {
                setFormError(t('two_factor.error_verify'));
            }

            setProcessing(false);
        }
    }

    function toggleRecoveryMode() {
        setShowRecoveryInput((current) => !current);
        setFormError(null);
        setFieldErrors({});
        setOtpCode('');
        setRecoveryCode('');
    }

    if (awaitingTwoFactor) {
        return (
            <>
                <Head title={t('two_factor.head_title')} />

                <form
                    onSubmit={handleChallenge}
                    className="flex flex-col gap-6"
                >
                    <div className="space-y-1 text-center">
                        <h2 className="text-lg font-medium">
                            {challengeCopy.title}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {challengeCopy.description}
                        </p>
                    </div>

                    {showRecoveryInput ? (
                        <div className="grid gap-2">
                            <Label htmlFor="recovery_code">
                                {t('two_factor.recovery_label')}
                            </Label>
                            <Input
                                id="recovery_code"
                                type="text"
                                autoFocus
                                required
                                value={recoveryCode}
                                onChange={(event) =>
                                    setRecoveryCode(event.target.value)
                                }
                                placeholder={t(
                                    'two_factor.recovery_placeholder',
                                )}
                            />
                            <InputError message={fieldErrors.recovery_code} />
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center space-y-3">
                            <InputOTP
                                maxLength={OTP_MAX_LENGTH}
                                value={otpCode}
                                onChange={setOtpCode}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                                autoFocus
                            >
                                <InputOTPGroup>
                                    {Array.from(
                                        { length: OTP_MAX_LENGTH },
                                        (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                            />
                                        ),
                                    )}
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={fieldErrors.code} />
                        </div>
                    )}

                    {formError && (
                        <p className="text-sm text-destructive" role="alert">
                            {formError}
                        </p>
                    )}

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={
                            processing ||
                            (showRecoveryInput
                                ? recoveryCode.length === 0
                                : otpCode.length < OTP_MAX_LENGTH)
                        }
                    >
                        {processing && <Spinner />}
                        {t('continue', { ns: 'common' })}
                    </Button>

                    <div className="text-center text-sm text-muted-foreground">
                        <span>{t('two_factor.or_you_can')}</span>
                        <button
                            type="button"
                            className="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                            onClick={toggleRecoveryMode}
                        >
                            {challengeCopy.toggleText}
                        </button>
                    </div>
                </form>
            </>
        );
    }

    return (
        <>
            <Head title={t('login.head_title')} />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">
                            {t('email_address', { ns: 'common' })}
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            placeholder={t('login.placeholder_email')}
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                        />
                        <InputError message={fieldErrors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            {t('password', { ns: 'common' })}
                        </Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder={t('login.placeholder_password')}
                            value={password}
                            onChange={(event) =>
                                setPassword(event.target.value)
                            }
                        />
                        <InputError message={fieldErrors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            tabIndex={3}
                            checked={remember}
                            onCheckedChange={(checked) =>
                                setRemember(checked === true)
                            }
                        />
                        <Label htmlFor="remember">
                            {t('login.remember_me')}
                        </Label>
                    </div>

                    {formError && (
                        <p className="text-sm text-destructive" role="alert">
                            {formError}
                        </p>
                    )}

                    <Button
                        type="submit"
                        className="mt-4 w-full"
                        tabIndex={4}
                        disabled={processing}
                        data-test="login-button"
                    >
                        {processing && <Spinner />}
                        {t('login.submit')}
                    </Button>
                </div>
            </form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: i18n.t('auth:login.layout_title'),
    description: i18n.t('auth:login.layout_description'),
};
