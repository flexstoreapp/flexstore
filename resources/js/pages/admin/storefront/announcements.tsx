import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { extractInstruction } from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { Link, router } from '@inertiajs/react';
import { MegaphoneIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import AnnouncementReorderController from '@/actions/App/Http/Controllers/Admin/AnnouncementReorderController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import { useConfirm } from '@/components/admin/confirm';
import { AnnouncementItem } from '@/components/admin/storefront/announcement-row';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { useStorefrontBuilder, useStorefrontBuilderAction } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Announcement } from '@/types';

export default function Announcements({ announcements: initialAnnouncements }: { announcements: Announcement[] }) {
    const [announcements, setAnnouncements] = useState(initialAnnouncements);
    const [prevInitial, setPrevInitial] = useState(initialAnnouncements);
    const { reloadIframe } = useStorefrontBuilder();
    const { confirm } = useConfirm();

    useStorefrontBuilderAction(
        <Can permission={Permission.StorefrontUpdate}>
            <Button asChild>
                <Link href={AnnouncementController.create()} prefetch>
                    {__('Add announcement')}
                </Link>
            </Button>
        </Can>,
    );

    if (initialAnnouncements !== prevInitial) {
        setPrevInitial(initialAnnouncements);
        setAnnouncements(initialAnnouncements);
    }

    useEffect(() => {
        return monitorForElements({
            onDrop({ location, source }) {
                if (!location.current.dropTargets.length) return;

                const draggedIndex = source.data.index as number;
                const target = location.current.dropTargets[0];
                const targetIndex = target.data.index as number;

                if (draggedIndex === targetIndex) return;

                const instruction = extractInstruction(target.data);
                if (!instruction) return;

                let newIndex = targetIndex;
                if (instruction.operation === 'reorder-before' && draggedIndex > targetIndex) {
                    newIndex = targetIndex;
                } else if (instruction.operation === 'reorder-before' && draggedIndex < targetIndex) {
                    newIndex = targetIndex - 1;
                }

                const newAnnouncements = [...announcements];
                const [removed] = newAnnouncements.splice(draggedIndex, 1);
                newAnnouncements.splice(newIndex, 0, removed);
                setAnnouncements(newAnnouncements);

                router.patch(
                    AnnouncementReorderController(),
                    { ordered_ids: newAnnouncements.map((announcement) => announcement.id) },
                    {
                        preserveScroll: true,
                        onSuccess: () => reloadIframe(),
                    },
                );
            },
        });
    }, [announcements, reloadIframe]);

    const handleDelete = (announcement: Announcement) => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this announcement.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(AnnouncementController.destroy(announcement), {
                        preserveScroll: true,
                        onSuccess: () => reloadIframe(),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    const handleToggleActive = (announcement: Announcement) => {
        router.patch(
            AnnouncementController.update(announcement),
            { is_active: !announcement.is_active },
            {
                preserveScroll: true,
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <div className="mb-6 space-y-4 p-4 text-sm">
            {announcements.length === 0 ? (
                <div className="rounded-lg border border-dashed p-6 text-center">
                    <MegaphoneIcon className="mx-auto size-8 text-muted-foreground/50" />
                    <p className="mt-2 text-muted-foreground">{__('No announcements')}</p>
                    <p className="mt-1 text-xs text-muted-foreground/70">
                        {__('Add announcements to display important messages to your customers.')}
                    </p>
                </div>
            ) : (
                <div className="space-y-2">
                    {announcements.map((announcement, index) => (
                        <AnnouncementItem
                            key={announcement.id}
                            announcement={announcement}
                            index={index}
                            onDelete={handleDelete}
                            onToggleActive={handleToggleActive}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

Announcements.layout = {
    title: __('Announcements'),
    backHref: StorefrontController.index(),
};
