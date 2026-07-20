import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import i18n from '@/lib/i18n';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation('settings');

    return (
        <>
            <Head title={t('profile.appearance_title')} />

            <h1 className="sr-only">{t('profile.appearance_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('profile.appearance_title')}
                    description={t('profile.appearance_description')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: i18n.t('settings:profile.appearance_title'),
            href: editAppearance(),
        },
    ],
};
