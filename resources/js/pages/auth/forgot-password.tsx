import { Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import i18n from '@/lib/i18n';
import { forgotPassword } from '@/lib/api/modules/auth';
import { login } from '@/routes';

export default function ForgotPassword() {
    const { t } = useTranslation(['auth', 'common']);
    const [email, setEmail] = useState('');
    const [processing, setProcessing] = useState(false);
    const [status, setStatus] = useState<string | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setProcessing(true);
        setStatus(null);
        setFormError(null);
        setFieldErrors({});

        try {
            const message = await forgotPassword(email);
            setStatus(message ?? t('forgot.success_fallback'));
            setEmail('');
        } catch (error) {
            if (error instanceof ApiError) {
                setFormError(error.message);
                setFieldErrors(error.fieldErrors);
            } else {
                setFormError(t('forgot.error_generic'));
            }
        } finally {
            setProcessing(false);
        }
    }

    return (
        <>
            <Head title={t('forgot.head_title')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">
                            {t('email_address', { ns: 'common' })}
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="off"
                            autoFocus
                            required
                            placeholder={t('login.placeholder_email')}
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                        />
                        <InputError message={fieldErrors.email} />
                    </div>

                    {formError && (
                        <p className="text-sm text-destructive" role="alert">
                            {formError}
                        </p>
                    )}

                    <div className="my-6 flex items-center justify-start">
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            {t('forgot.submit')}
                        </Button>
                    </div>
                </form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>{t('forgot.or_return')}</span>
                    <TextLink href={login()}>{t('forgot.log_in_link')}</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: i18n.t('auth:forgot.layout_title'),
    description: i18n.t('auth:forgot.layout_description'),
};
