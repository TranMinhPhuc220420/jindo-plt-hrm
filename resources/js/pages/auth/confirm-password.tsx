import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import i18n from '@/lib/i18n';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const { t } = useTranslation(['auth', 'common']);

    return (
        <>
            <Head title={t('confirm.head_title')} />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={t('confirm.passkey_label')}
                loadingLabel={t('confirm.passkey_loading')}
                separator={t('confirm.passkey_separator')}
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('password', { ns: 'common' })}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t('login.placeholder_password')}
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {t('confirm.submit')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: i18n.t('auth:confirm.layout_title'),
    description: i18n.t('auth:confirm.layout_description'),
};
