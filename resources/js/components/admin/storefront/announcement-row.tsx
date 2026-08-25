import {
    attachInstruction,
    extractInstruction,
    type Instruction,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { Link } from '@inertiajs/react';
import { EyeIcon, EyeOffIcon, GripVerticalIcon, MegaphoneIcon, PencilIcon, Trash2Icon } from 'lucide-react';
import { m, type Transition } from 'motion/react';
import { useRef } from 'react';

import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import { DropIndicator } from '@/components/admin/drop-indicator';
import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { useDragReorderItem } from '@/hooks/admin/use-drag-reorder-item';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { Announcement } from '@/types';

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.2,
};

function DragPreview({ announcement }: { announcement: Announcement }) {
    return (
        <div className="flex items-center gap-3 rounded-md border bg-popover p-3">
            <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
                <MegaphoneIcon className="size-5" />
            </div>
            <p className="max-w-40 truncate text-sm font-medium">{getTranslation(announcement.content)}</p>
        </div>
    );
}

interface AnnouncementItemProps {
    announcement: Announcement;
    index: number;
    onDelete: (announcement: Announcement) => void;
    onToggleActive: (announcement: Announcement) => void;
}

export function AnnouncementItem({ announcement, index, onDelete, onToggleActive }: AnnouncementItemProps) {
    const ref = useRef<HTMLDivElement>(null);
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.StorefrontUpdate);
    const { isDragging, instruction } = useDragReorderItem<HTMLDivElement, Instruction>({
        ref,
        enabled: canUpdate,
        getInitialData: () => ({ index, announcementId: announcement.id }),
        getData: ({ input, element }) =>
            attachInstruction({ index }, { input, element, operations: { 'reorder-before': 'available' } }),
        extractInstruction,
        canDrop: ({ source }) => source.data.index !== index,
        renderPreview: () => <DragPreview announcement={announcement} />,
    });

    const content = getTranslation(announcement.content);

    return (
        <m.div layout transition={animationTransition} className="relative">
            <m.div
                layout
                ref={ref}
                transition={animationTransition}
                className={cn(
                    'group/item relative flex items-center justify-between gap-2 rounded-lg border bg-background p-3',
                    'transition-all hover:bg-muted/50',
                    (!announcement.is_active || isDragging) && 'opacity-60 hover:bg-background',
                    canUpdate && 'cursor-grab active:cursor-grabbing',
                )}
            >
                <div className="flex items-center justify-center gap-2">
                    <Can permission={Permission.StorefrontUpdate}>
                        <GripVerticalIcon className="size-4 text-muted-foreground" />
                    </Can>
                    <div className="flex flex-1 items-center justify-center gap-3">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <MegaphoneIcon className="size-5" />
                        </div>
                        <p className="text-sm font-medium">{content}</p>
                    </div>
                </div>
                <HoverActions>
                    <Can permission={Permission.StorefrontUpdate}>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={announcement.is_active ? 'Hide announcement' : 'Show announcement'}
                            onClick={() => onToggleActive(announcement)}
                        >
                            {announcement.is_active ? <EyeIcon /> : <EyeOffIcon />}
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={__('Edit announcement')}
                            asChild
                        >
                            <Link href={AnnouncementController.edit(announcement)}>
                                <PencilIcon />
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={__('Delete announcement')}
                            onClick={() => onDelete(announcement)}
                        >
                            <Trash2Icon />
                        </Button>
                    </Can>
                </HoverActions>

                {instruction?.operation === 'reorder-before' && <DropIndicator edge="top" />}
            </m.div>
        </m.div>
    );
}
