import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export function useCurrencyDecimalPlaces(currencyCode?: string): number {
    const { baseCurrency, availableCurrencies } = usePage().props;
    const code = currencyCode ?? baseCurrency;

    return useMemo(() => {
        const currency = availableCurrencies.find((c) => c.code === code);
        return currency?.decimal_places ?? 2;
    }, [code, availableCurrencies]);
}

export function useMoneyInputProps(currencyCode?: string) {
    const decimalPlaces = useCurrencyDecimalPlaces(currencyCode);

    return useMemo(
        () => ({
            type: 'number' as const,
            inputMode: 'decimal' as const,
            min: '0',
            step: decimalPlaces > 0 ? `0.${'0'.repeat(decimalPlaces - 1)}1` : '1',
            placeholder: decimalPlaces > 0 ? `0.${'0'.repeat(decimalPlaces)}` : '0',
        }),
        [decimalPlaces],
    );
}
