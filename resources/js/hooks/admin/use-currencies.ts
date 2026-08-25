import { useMemo } from 'react';

import currencies from '@/lib/currencies.json';

export function useCurrencies() {
    const currencyNames = useMemo(
        () =>
            Object.entries(currencies).reduce(
                (acc, [code, { name }]) => ({ ...acc, [code]: name }),
                {} as Record<string, string>,
            ),
        [],
    );

    const currencySymbols = useMemo(
        () =>
            Object.entries(currencies).reduce(
                (acc, [code, { symbol }]) => ({ ...acc, [code]: symbol }),
                {} as Record<string, string>,
            ),
        [],
    );

    const currencyOptions = useMemo(
        () =>
            Object.entries(currencyNames).map(([code, name]) => ({
                value: code,
                label: `${name} (${code})`,
            })),
        [currencyNames],
    );

    return {
        currencyNames,
        currencySymbols,
        currencyOptions,
    };
}
