import fs from 'node:fs';
import path from 'node:path';

import { setTranslations } from '@/lib/i18n';

const BASE_LOCALE = 'en';
const COMMON_BUNDLE = 'common';

const cache = new Map<string, Record<string, string>>();

export function loadServerTranslations(component: string, locale: string): void {
    const bundle = bundleFor(component);

    if (bundle === null) {
        setTranslations({});

        return;
    }

    const key = `${bundle}:${locale}:${signature(bundle, locale)}`;
    const cached = cache.get(key);

    if (cached) {
        setTranslations(cached);

        return;
    }

    const translations = {
        ...read(COMMON_BUNDLE, BASE_LOCALE),
        ...read(COMMON_BUNDLE, locale),
        ...read(bundle, BASE_LOCALE),
        ...read(bundle, locale),
    };

    for (const staleKey of cache.keys()) {
        if (staleKey.startsWith(`${bundle}:${locale}:`)) {
            cache.delete(staleKey);
        }
    }

    cache.set(key, translations);

    setTranslations(translations);
}

function bundleFor(component: string): string | null {
    if (component.startsWith('installer/')) return null;
    if (component.startsWith('admin/')) return 'admin';

    return 'storefront';
}

function bundlePath(bundle: string, locale: string): string {
    return path.join(process.cwd(), 'lang', bundle, `${locale}.json`);
}

function signature(bundle: string, locale: string): string {
    return [COMMON_BUNDLE, bundle]
        .map((group) => {
            try {
                const stats = fs.statSync(bundlePath(group, locale));

                return `${stats.mtimeMs}:${stats.size}`;
            } catch {
                return '';
            }
        })
        .join('|');
}

function read(bundle: string, locale: string): Record<string, string> {
    try {
        return JSON.parse(fs.readFileSync(bundlePath(bundle, locale), 'utf8'));
    } catch {
        return {};
    }
}
