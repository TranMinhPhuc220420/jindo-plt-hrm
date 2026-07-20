import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ApiError } from '@/lib/api/errors';
import { updateLocale } from '@/lib/api/modules/auth';
import { useAuth } from '@/lib/auth/auth-context';
import {
    applyLocale,
    SUPPORTED_LOCALES,
    type AppLocale,
} from '@/lib/i18n';

type Props = {
    variant?: 'ghost' | 'outline';
    className?: string;
};

export function LanguageSwitcher({
    variant = 'ghost',
    className,
}: Props) {
    const { t, i18n } = useTranslation('common');
    const { setSession, user } = useAuth();
    const [saving, setSaving] = useState(false);

    const current = (SUPPORTED_LOCALES.includes(i18n.language as AppLocale)
        ? i18n.language
        : 'vi') as AppLocale;

    async function selectLocale(locale: AppLocale) {
        if (locale === current || saving) {
            return;
        }

        setSaving(true);

        try {
            if (user) {
                const payload = await updateLocale(locale);
                setSession(payload);
                await applyLocale(payload.locale);
            } else {
                await applyLocale(locale);
            }
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('request_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant={variant}
                    size="sm"
                    className={className}
                    disabled={saving}
                    aria-label={t('language')}
                >
                    {current === 'vi' ? t('locale_vi') : t('locale_en')}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {SUPPORTED_LOCALES.map((locale) => (
                    <DropdownMenuItem
                        key={locale}
                        onSelect={() => {
                            void selectLocale(locale);
                        }}
                    >
                        {locale === 'vi' ? t('locale_vi') : t('locale_en')}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
