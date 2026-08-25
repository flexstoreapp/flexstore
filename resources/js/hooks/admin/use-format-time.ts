import { usePage } from '@inertiajs/react';

export function useFormatTime() {
    const { activeLocale } = usePage().props;

    return (value: number | string | Date) => {
        return new Date(value).toLocaleTimeString(activeLocale, {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
    };
}
