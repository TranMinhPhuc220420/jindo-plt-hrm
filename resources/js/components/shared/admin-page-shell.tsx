import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import Heading from '@/components/heading';
import { PermissionGate } from '@/components/shared/permission-gate';

type Props = {
    title: string;
    description: string;
    permission?: string;
    any?: string[];
    actions?: ReactNode;
    children?: ReactNode;
};

export default function AdminPageShell({
    title,
    description,
    permission,
    any,
    actions,
    children,
}: Props) {
    const { t } = useTranslation('common');

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <div className="flex items-start justify-between gap-4 [&>header]:mb-0">
                    <Heading title={title} description={description} />
                    {actions ? (
                        <div className="shrink-0 pt-0.5">{actions}</div>
                    ) : null}
                </div>
                <PermissionGate
                    permission={permission}
                    any={any}
                    fallback={
                        <div className="rounded-xl border border-border bg-card p-6 text-sm text-muted-foreground">
                            {t('permission_denied')}
                        </div>
                    }
                >
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6">
                        {children}
                    </div>
                </PermissionGate>
            </div>
        </>
    );
}
