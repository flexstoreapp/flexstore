import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

import type { Currency } from '@/types';

type Format = Pick<
    Currency,
    'code' | 'symbol' | 'symbol_position' | 'thousands_separator' | 'decimal_separator' | 'decimal_places'
>;

const DEFAULT_FORMAT: Format = {
    code: 'USD',
    symbol: '$',
    symbol_position: 'before_with_space',
    thousands_separator: ',',
    decimal_separator: '.',
    decimal_places: 2,
};

export function useFormatMoney() {
    const { activeCurrency, availableCurrencies } = usePage().props;

    const currencyMap = useMemo(() => new Map(availableCurrencies.map((c) => [c.code, c])), [availableCurrencies]);

    const rate = parseFloat(currencyMap.get(activeCurrency)?.exchange_rate ?? '1') || 1;

    const currencyFormat = useMemo(() => {
        const currency = currencyMap.get(activeCurrency);

        if (!currency) {
            return { ...DEFAULT_FORMAT, code: activeCurrency.toLowerCase(), symbol: activeCurrency };
        }

        return {
            code: currency.code.toLowerCase(),
            symbol: currency.symbol,
            symbol_position: currency.symbol_position,
            thousands_separator: currency.thousands_separator,
            decimal_separator: currency.decimal_separator,
            decimal_places: currency.decimal_places,
        };
    }, [activeCurrency, currencyMap]);

    const formatMoney = useCallback(
        (value: string | number, currencyCode?: string): string => {
            const numericValue = parseNumericValue(value);

            if (currencyCode) {
                const format = resolveFormat(currencyCode, currencyMap, currencyFormat);
                return formatValue(numericValue, format);
            }

            return formatValue(numericValue * rate, currencyFormat);
        },
        [currencyFormat, rate, currencyMap],
    );

    const convertToBaseCurrency = useCallback((value: string | number, exchangeRate: string | number): number => {
        const numericValue = parseNumericValue(value);
        const rate = parseNumericValue(exchangeRate) || 1;
        return numericValue / rate;
    }, []);

    return { formatMoney, currencyFormat, convertToBaseCurrency };
}

function resolveFormat(
    currencyCode: string,
    currencyMap: Map<
        string,
        Pick<
            Currency,
            'code' | 'symbol' | 'symbol_position' | 'thousands_separator' | 'decimal_separator' | 'decimal_places'
        >
    >,
    fallback: Format,
): Format {
    const currency = currencyMap.get(currencyCode);

    if (!currency) {
        return {
            code: currencyCode,
            symbol: currencyCode,
            symbol_position: 'before_with_space',
            thousands_separator: fallback.thousands_separator,
            decimal_separator: fallback.decimal_separator,
            decimal_places: fallback.decimal_places,
        };
    }

    return {
        code: currencyCode.toLowerCase(),
        symbol: currency.symbol,
        symbol_position: currency.symbol_position,
        thousands_separator: currency.thousands_separator,
        decimal_separator: currency.decimal_separator,
        decimal_places: currency.decimal_places,
    };
}

export function formatMoney(value: number, format: Format): string {
    return formatValue(value, format);
}

function formatValue(value: number, format: Format): string {
    const { symbol, symbol_position, thousands_separator, decimal_separator, decimal_places } = format;
    const places = decimal_places ?? 2;
    const isNegative = value < 0;
    const formattedValue = Math.abs(value).toFixed(places);

    const parts = formattedValue.split('.');
    const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, () => thousands_separator);
    const decimalPart = parts[1] ?? '';

    const formattedNumber = decimalPart ? `${integerPart}${decimal_separator}${decimalPart}` : integerPart;
    const sign = isNegative ? '-' : '';

    switch (symbol_position) {
        case 'after':
            return `${sign}${formattedNumber}${symbol}`;
        case 'after_with_space':
            return `${sign}${formattedNumber} ${symbol}`;
        case 'before_with_space':
            return `${sign}${symbol} ${formattedNumber}`;
        case 'before':
        default:
            return `${sign}${symbol}${formattedNumber}`;
    }
}

function parseNumericValue(value: string | number): number {
    if (typeof value === 'number') {
        return isNaN(value) ? 0 : value;
    }

    const numericValue = parseFloat(value);
    return isNaN(numericValue) ? 0 : numericValue;
}
