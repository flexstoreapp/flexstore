import { Link } from '@inertiajs/react';
import { DownloadIcon } from 'lucide-react';

import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { AccountEmptyState } from '@/components/storefront/account/account-empty-state';
import { buttonVariants } from '@/components/storefront/button';
import { __ } from '@/lib/i18n';

export function DownloadsEmptyState() {
    return (
        <div className="rounded-md border border-line bg-surface">
            <AccountEmptyState
                icon={<DownloadIcon size={28} strokeWidth={1.6} aria-hidden="true" />}
                title={__('No downloads yet')}
                description={__('Digital products you purchase will appear here, ready to download.')}
                action={
                    <Link href={ShopController.index()} className={buttonVariants({ variant: 'primary', size: 'md' })}>
                        {__('Browse the shop')}
                    </Link>
                }
            />
        </div>
    );
}
