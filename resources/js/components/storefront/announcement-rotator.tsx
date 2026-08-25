import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { cn, getTranslation } from '@/lib/utils';
import type { StorefrontAnnouncement } from '@/types';

const itemClass =
    'col-start-1 row-start-1 truncate transition-[translate,opacity] duration-(--duration-base) ease-out-quart';

export function AnnouncementRotator({ announcements }: { announcements: StorefrontAnnouncement[] }) {
    const [active, setActive] = useState(0);
    const [leaving, setLeaving] = useState<number | null>(null);

    useEffect(() => {
        if (announcements.length < 2) {
            return;
        }

        if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const timer = setInterval(() => {
            setActive((current) => {
                setLeaving(current);
                return (current + 1) % announcements.length;
            });
        }, 4000);

        return () => clearInterval(timer);
    }, [announcements.length]);

    if (announcements.length === 0) {
        return null;
    }

    return (
        <div className="grid min-w-0 overflow-hidden pe-3">
            {announcements.map((announcement, index) => {
                const content = getTranslation(announcement.content);

                return (
                    <span
                        key={announcement.id}
                        className={cn(
                            itemClass,
                            index === active
                                ? 'translate-y-0 opacity-100'
                                : index === leaving
                                  ? '-translate-y-full opacity-0'
                                  : 'translate-y-full opacity-0',
                        )}
                        aria-hidden={index !== active}
                    >
                        {announcement.url ? (
                            <Link href={announcement.url} prefetch className="transition-colors hover:text-white">
                                {content}
                            </Link>
                        ) : (
                            content
                        )}
                    </span>
                );
            })}
        </div>
    );
}
