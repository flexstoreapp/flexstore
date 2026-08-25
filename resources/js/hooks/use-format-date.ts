import { usePage } from '@inertiajs/react';

export function useFormatDate() {
    const { activeLocale } = usePage().props;

    return (value: number | string | Date, dateFormat?: Intl.DateTimeFormatOptions) => {
        return new Date(value).toLocaleDateString(activeLocale, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour12: true,
            ...dateFormat,
        });
    };
}
