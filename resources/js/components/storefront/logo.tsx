import { Link, usePage } from '@inertiajs/react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import type { StorefrontSharedData } from '@/types';

export function Logo() {
    const { storeName, storeLogo } = usePage<StorefrontSharedData>().props;

    return (
        <Link href={HomepageController.show()} className="flex items-center transition-colors hover:text-inherit">
            {storeLogo?.url ? (
                <img src={storeLogo.url} alt={storeName} className="h-9 w-auto lg:h-11" />
            ) : (
                <span className="font-head text-5xl font-bold tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    {storeName}
                </span>
            )}
        </Link>
    );
}
