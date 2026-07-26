import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { COMPANY_NAME, DEFAULT_APP_NAME } from '@/lib/brand';

export default function AppLogo() {
    const { name } = usePage().props;
    const productName = name || DEFAULT_APP_NAME;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">{productName}</span>
                <span className="truncate text-xs text-muted-foreground">
                    {COMPANY_NAME}
                </span>
            </div>
        </>
    );
}
