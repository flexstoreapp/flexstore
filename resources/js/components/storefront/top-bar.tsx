import { Link } from '@inertiajs/react';

import * as TrackOrderController from '@/actions/App/Http/Controllers/Storefront/TrackOrderController';
import { __ } from '@/lib/i18n';
import type { StorefrontAnnouncement } from '@/types';

import { AccountMenu } from './account-menu';
import { AnnouncementRotator } from './announcement-rotator';

interface TopBarProps {
    announcements: StorefrontAnnouncement[];
}

export function TopBar({ announcements }: TopBarProps) {
    return (
        <div className="relative z-50 bg-ink text-sm text-inverse-text [&_:focus-visible]:outline-white">
            <div className="mx-auto flex h-9 w-full max-w-page items-center justify-between px-6">
                <AnnouncementRotator announcements={announcements} />
                <nav className="ms-auto flex items-center gap-5">
                    <AccountMenu />
                    <span className="hidden text-muted lg:block" aria-hidden="true">
                        |
                    </span>
                    <Link
                        href={TrackOrderController.create()}
                        className="hidden transition-colors hover:text-white lg:block"
                    >
                        {__('Track order')}
                    </Link>
                </nav>
            </div>
        </div>
    );
}
