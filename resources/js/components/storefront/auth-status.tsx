import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

export function AuthStatus() {
    const { message, error } = usePage<StorefrontSharedData>().props.flash;

    if (!message && !error) {
        return null;
    }

    return (
        <div
            role={error ? 'alert' : 'status'}
            className={cn(
                'mt-6 rounded-md px-4 py-3 text-sm font-medium',
                error ? 'bg-error/10 text-error' : 'bg-success/10 text-success',
            )}
        >
            {error ?? message}
        </div>
    );
}
