import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import * as RPNInput from 'react-phone-number-input';

export function usePhoneCountries({ all = false }: { all?: boolean } = {}): RPNInput.Country[] {
    const { sellingCountries } = usePage().props;

    return useMemo(() => {
        const dialable = RPNInput.getCountries();

        return all ? dialable : dialable.filter((country) => sellingCountries.includes(country));
    }, [all, sellingCountries]);
}
