import {
    attachInstruction,
    extractInstruction,
    type Instruction,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/list-item';
import { Link } from '@inertiajs/react';
import {
    BadgeCheckIcon,
    Columns3Icon,
    EyeIcon,
    EyeOffIcon,
    GalleryHorizontalIcon,
    GripVerticalIcon,
    ImageIcon,
    ImagesIcon,
    LayoutGridIcon,
    LayoutPanelTopIcon,
    MessageSquareQuoteIcon,
    PencilIcon,
    StarIcon,
    TagsIcon,
    Trash2Icon,
} from 'lucide-react';
import { m, type Transition } from 'motion/react';
import { useRef } from 'react';

import * as StorefrontSectionController from '@/actions/App/Http/Controllers/Admin/StorefrontSectionController';
import { DropIndicator } from '@/components/admin/drop-indicator';
import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { useDragReorderItem } from '@/hooks/admin/use-drag-reorder-item';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { StorefrontSection, StorefrontSectionType } from '@/types';

export const sectionTypes: Record<StorefrontSectionType, string> = {
    hero_slider: __('Hero slider'),
    info_strip: __('Info strip'),
    category_grid: __('Category grid'),
    featured_products: __('Featured products'),
    product_tabs: __('Product tabs'),
    promo_banners: __('Promo banners'),
    product_carousel: __('Product carousel'),
    product_columns: __('Product columns'),
    brand_strip: __('Brand strip'),
    testimonials: __('Testimonials'),
};

export const sectionTypeIcons: Record<StorefrontSectionType, React.ReactNode> = {
    hero_slider: <ImagesIcon className="size-5" />,
    info_strip: <BadgeCheckIcon className="size-5" />,
    category_grid: <LayoutGridIcon className="size-5" />,
    featured_products: <StarIcon className="size-5" />,
    product_tabs: <LayoutPanelTopIcon className="size-5" />,
    promo_banners: <ImageIcon className="size-5" />,
    product_carousel: <GalleryHorizontalIcon className="size-5" />,
    product_columns: <Columns3Icon className="size-5" />,
    brand_strip: <TagsIcon className="size-5" />,
    testimonials: <MessageSquareQuoteIcon className="size-5" />,
};

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.2,
};

function DragPreview({ section }: { section: StorefrontSection }) {
    return (
        <div className="flex items-center gap-3 rounded-md border bg-popover p-3">
            <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
                {sectionTypeIcons[section.type]}
            </div>
            <div className="space-y-0.5">
                <p className="max-w-40 truncate text-sm font-medium">{getTranslation(section.title)}</p>
                <p className="text-xs text-muted-foreground">{sectionTypes[section.type]}</p>
            </div>
        </div>
    );
}

interface SectionItemProps {
    section: StorefrontSection;
    index: number;
    onDelete: (section: StorefrontSection) => void;
    onToggleActive: (section: StorefrontSection) => void;
}

export function SectionItem({ section, index, onDelete, onToggleActive }: SectionItemProps) {
    const buttonRef = useRef<HTMLDivElement>(null);
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.StorefrontUpdate);
    const { isDragging, instruction } = useDragReorderItem<HTMLDivElement, Instruction>({
        ref: buttonRef,
        enabled: canUpdate,
        getInitialData: () => ({ index, sectionId: section.id }),
        getData: ({ input, element }) =>
            attachInstruction({ index }, { input, element, operations: { 'reorder-before': 'available' } }),
        extractInstruction,
        canDrop: ({ source }) => source.data.index !== index,
        renderPreview: () => <DragPreview section={section} />,
    });

    return (
        <m.div layout transition={animationTransition} className="relative">
            <m.div
                layout
                ref={buttonRef}
                transition={animationTransition}
                className={cn(
                    'group/item relative flex items-center justify-between gap-2 rounded-lg border bg-background p-3',
                    'transition-all hover:bg-muted/50',
                    (!section.is_active || isDragging) && 'opacity-60 hover:bg-background',
                    canUpdate && 'cursor-grab active:cursor-grabbing',
                )}
            >
                <div className="flex items-center justify-center gap-2">
                    <Can permission={Permission.StorefrontUpdate}>
                        <GripVerticalIcon className="size-4 text-muted-foreground" />
                    </Can>
                    <div className="flex flex-1 items-center justify-center gap-3">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            {sectionTypeIcons[section.type]}
                        </div>
                        <div className="space-y-0.5">
                            <p className="text-sm font-medium">{getTranslation(section.title)}</p>
                            <p className="text-xs text-muted-foreground">{sectionTypes[section.type]}</p>
                        </div>
                    </div>
                </div>
                <HoverActions>
                    <Can permission={Permission.StorefrontUpdate}>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={section.is_active ? 'Hide section' : 'Show section'}
                            onClick={() => onToggleActive(section)}
                        >
                            {section.is_active ? <EyeIcon /> : <EyeOffIcon />}
                        </Button>
                        <Button variant="ghost" size="icon" className="size-7" aria-label={__('Edit section')} asChild>
                            <Link href={StorefrontSectionController.edit(section)}>
                                <PencilIcon />
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={__('Delete section')}
                            onClick={() => onDelete(section)}
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
