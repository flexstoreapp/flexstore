import { cn } from '@/lib/utils';
import type { StorefrontMenuItem } from '@/types';

import { FLYOUT_TRANSITION } from './flyout';
import { MegaContent, toMegaSections } from './mega-content';

export function NavMegaMenu({ item }: { item: StorefrontMenuItem }) {
    return (
        <div
            className={cn(
                FLYOUT_TRANSITION,
                'start-0 end-0 overflow-hidden rounded-b-md border border-line bg-surface shadow-md group-focus-within/mega:visible group-focus-within/mega:scale-y-100 group-focus-within/mega:opacity-100 group-hover/mega:visible group-hover/mega:scale-y-100 group-hover/mega:opacity-100 group-hover/mega:delay-60 group-hover/mega:duration-(--duration-fast)',
            )}
        >
            <MegaContent
                sections={toMegaSections(item)}
                featured={item.featured}
                footerHref={item.url}
                columnsClassName="grid-cols-3 xl:grid-cols-4"
            />
        </div>
    );
}
