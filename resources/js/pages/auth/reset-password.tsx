import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ApiError } from '@/lib/api/errors';
import { resetPassword } from '@/lib/api/modules/auth';
import i18n from '@/lib/i18n';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    const { t } = useTranslation(['auth', 'common']);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [processing, setProcessing] = useState(false);
    const [formError, setFormError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setProcessing(true);
        setFormError(null);
        setFieldErrors({});

        try {
            await resetPassword({
                token,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            router.visit('/login');
        } catch (error) {
            if (error instanceof ApiError) {
                setFormError(error.message);
                setFieldErrors(error.fieldErrors);
            } else {
                setFormError(t('reset.error_generic'));
            }

            setProcessing(false);
        }
    }

    return (
        <>
            <Head title={t('reset.head_title')} />

            <form onSubmit={handleSubmit} className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="email">
                        {t('email', { ns: 'common' })}
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autoComplete="email"
                        value={email}
                        className="mt-1 block w-full"
                        readOnly
                    />
                    <InputError message={fieldErrors.email} className="mt-2" />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">
                        {t('password', { ns: 'common' })}
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autoComplete="new-password"
                        className="mt-1 block w-full"
                        autoFocus
                        required
                        placeholder={t('login.placeholder_password')}
                        passwordrules={passwordRules}
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                    />
                    <InputError message={fieldErrors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        {t('register.confirm_password')}
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autoComplete="new-password"
                        className="mt-1 block w-full"
                        required
                        placeholder={t('register.confirm_password_placeholder')}
                        passwordrules={passwordRules}
                        value={passwordConfirmation}
                        onChange={(event) =>
                            setPasswordConfirmation(event.target.value)
                        }
                    />
                    <InputError
                        message={fieldErrors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                {formError && (
                    <p className="text-sm text-destructive" role="alert">
                        {formError}
                    </p>
                )}

                <Button
                    type="submit"
                    className="mt-4 w-full"
                    disabled={processing}
                    data-test="reset-password-button"
                >
                    {processing && <Spinner />}
                    {t('reset.submit')}
                </Button>
            </form>
        </>
    );
}

ResetPassword.layout = {
    title: i18n.t('auth:reset.layout_title'),
    description: i18n.t('auth:reset.layout_description'),
};
