import { Form, Head, router, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { AvatarEditor } from '@/components/shared/avatar-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as authApi from '@/lib/api/modules/auth';
import { useAuth } from '@/lib/auth/auth-context';
import i18n from '@/lib/i18n';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t } = useTranslation(['settings', 'common']);
    const { auth } = usePage<PageProps>().props;
    const { employeeId, setSession, user: sessionUser } = useAuth();

    const avatarUrl = sessionUser?.avatar ?? auth.user.avatar ?? null;

    async function handleAvatarUpload(file: File) {
        const payload = await authApi.uploadMyAvatar(file);
        setSession(payload);
        router.reload({ only: ['auth'] });
    }

    async function handleAvatarRemove() {
        const payload = await authApi.deleteMyAvatar();
        setSession(payload);
        router.reload({ only: ['auth'] });
    }

    return (
        <>
            <Head title={t('profile.settings_title')} />

            <h1 className="sr-only">{t('profile.settings_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('profile.title')}
                    description={t('profile.settings_description')}
                />

                <div className="space-y-3 border-b border-border pb-6">
                    <div>
                        <h2 className="text-sm font-semibold">
                            {t('profile.avatar_title')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {employeeId
                                ? t('profile.avatar_description')
                                : t('profile.avatar_no_employee')}
                        </p>
                    </div>

                    {employeeId ? (
                        <AvatarEditor
                            name={auth.user.name}
                            avatarUrl={avatarUrl}
                            onUpload={handleAvatarUpload}
                            onRemove={handleAvatarRemove}
                        />
                    ) : null}
                </div>

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {t('name', { ns: 'common' })}
                                </Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder={t(
                                        'register.name_placeholder',
                                        {
                                            ns: 'auth',
                                        },
                                    )}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('email_address', { ns: 'common' })}
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder={t('email_address', {
                                        ns: 'common',
                                    })}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: i18n.t('settings:profile.settings_title'),
            href: edit(),
        },
    ],
};
