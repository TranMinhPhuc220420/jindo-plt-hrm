import * as settingsApi from '@/lib/api/modules/settings';
import { normalizeCurrency } from '@/lib/currency';
import type { AppCurrency } from '@/lib/currency';

/** Load company settings currency; defaults to VND. */
export async function loadCompanyCurrency(): Promise<AppCurrency> {
    try {
        const settings = await settingsApi.getSettings('company');
        const raw = settings?.company?.currency;

        return normalizeCurrency(typeof raw === 'string' ? raw : undefined);
    } catch {
        return 'VND';
    }
}
