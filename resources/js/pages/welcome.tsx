import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLogoIcon from '@/components/app-logo-icon';
import { LanguageSwitcher } from '@/components/shared/language-switcher';
import { COMPANY_NAME, DEFAULT_APP_NAME } from '@/lib/brand';
import { dashboard, login, register } from '@/routes';

/** Photo: Unsplash — team collaboration (photo-1522071820081-009f0129c71c). */
const HERO_SRC = '/images/welcome-hero.jpg';

export default function Welcome() {
    const { t } = useTranslation('welcome');
    const { auth, name } = usePage().props;
    const productName = name || DEFAULT_APP_NAME;
    const year = new Date().getFullYear();

    return (
        <>
            <Head title={t('head_title')} />
            <div className="relative flex min-h-svh flex-col overflow-hidden text-white">
                <img
                    src={HERO_SRC}
                    alt={t('hero_alt')}
                    className="absolute inset-0 size-full object-cover"
                    fetchPriority="high"
                />
                <div
                    className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/55 to-black/25"
                    aria-hidden
                />
                <div
                    className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/30"
                    aria-hidden
                />

                <header className="relative z-10 flex w-full items-center justify-between px-6 py-5 md:px-10">
                    <Link href="/" className="flex items-center gap-2.5">
                        <div className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground shadow-sm">
                            <AppLogoIcon className="size-5" />
                        </div>
                        <span className="leading-tight">
                            <span className="block text-sm font-semibold tracking-tight">
                                {productName}
                            </span>
                            <span className="block text-xs text-white/70">
                                {COMPANY_NAME}
                            </span>
                        </span>
                    </Link>
                    <nav className="flex items-center gap-2 text-sm sm:gap-3">
                        <LanguageSwitcher
                            variant="ghost"
                            className="text-white hover:bg-white/10 hover:text-white"
                        />
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex h-9 items-center rounded-md bg-primary px-4 font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                {t('dashboard')}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-flex h-9 items-center rounded-md px-3 font-medium text-white/90 hover:bg-white/10 hover:text-white sm:px-4"
                                >
                                    {t('log_in')}
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex h-9 items-center rounded-md border border-white/40 px-3 font-medium text-white hover:bg-white/10 sm:px-4"
                                >
                                    {t('register')}
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <main className="relative z-10 flex flex-1 flex-col justify-center px-6 pb-20 pt-8 md:px-10 md:pb-28">
                    <div className="mx-auto w-full max-w-2xl text-center md:mx-0 md:text-left">
                        <p className="mb-3 text-sm font-medium tracking-wide text-primary-brand-soft uppercase">
                            {productName}
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl md:text-5xl">
                            {t('headline')}
                        </h1>
                        <p className="mt-4 max-w-lg text-base text-white/80 text-pretty sm:text-lg md:mx-0 mx-auto">
                            {t('tagline', { company: COMPANY_NAME })}
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3 md:justify-start">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex h-11 items-center rounded-md bg-primary px-7 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                                >
                                    {t('go_dashboard')}
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-flex h-11 items-center rounded-md bg-primary px-7 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                                >
                                    {t('sign_in')}
                                </Link>
                            )}
                        </div>
                    </div>
                </main>

                <footer className="relative z-10 px-6 py-4 text-center text-xs text-white/55 md:px-10 md:text-left">
                    {t('copyright', { year, company: COMPANY_NAME })}
                </footer>
            </div>
        </>
    );
}
