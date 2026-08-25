import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import countries from '@/lib/countries.json';

export function useCountries({ all = false }: { all?: boolean } = {}): {
    countryNames: Record<string, string>;
    countryOptions: { value: string; label: string }[];
} {
    const { sellingCountries } = usePage().props;

    const countryNames = useMemo(
        () =>
            Object.entries(countries).reduce(
                (acc, [code, name]) => ({
                    ...acc,
                    [code]: name,
                }),
                {} as Record<string, string>,
            ),
        [],
    );

    const countryOptions = useMemo(() => {
        const entries = Object.entries(countries);
        const allowed = all ? entries : entries.filter(([code]) => sellingCountries.includes(code));

        return allowed.map(([value, label]) => ({ value, label }));
    }, [all, sellingCountries]);

    return {
        countryNames,
        countryOptions,
    };
}
