import {
    attachInstruction,
    extractInstruction,
    type Instruction,
    type ItemMode,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/tree-item';
import { type UrlMethodPair } from '@inertiajs/core';
import { Link, router } from '@inertiajs/react';
import {
    GripVerticalIcon,
    ExternalLinkIcon,
    FileTextIcon,
    ListTreeIcon,
    TagIcon,
    PencilIcon,
    Trash2Icon,
    EyeIcon,
    EyeOffIcon,
} from 'lucide-react';
import { m, type Transition } from 'motion/react';
import { useRef } from 'react';

import * as MenuItemController from '@/actions/App/Http/Controllers/Admin/MenuItemController';
import * as BrandController from '@/actions/App/Http/Controllers/Storefront/BrandController';
import * as BrandProductController from '@/actions/App/Http/Controllers/Storefront/BrandProductController';
import * as CategoryController from '@/actions/App/Http/Controllers/Storefront/CategoryController';
import * as CategoryProductController from '@/actions/App/Http/Controllers/Storefront/CategoryProductController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import * as PrivacyPolicyController from '@/actions/App/Http/Controllers/Storefront/PrivacyPolicyController';
import * as RefundPolicyController from '@/actions/App/Http/Controllers/Storefront/RefundPolicyController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import * as TermsOfServiceController from '@/actions/App/Http/Controllers/Storefront/TermsOfServiceController';
import * as TrackOrderController from '@/actions/App/Http/Controllers/Storefront/TrackOrderController';
import * as WishlistController from '@/actions/App/Http/Controllers/Storefront/WishlistController';
import { useConfirm } from '@/components/admin/confirm';
import { DropIndicator } from '@/components/admin/drop-indicator';
import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { useDragReorderItem } from '@/hooks/admin/use-drag-reorder-item';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __, transChoice } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { LinkType, MenuItem, MenuPage } from '@/types';

const PAGE_URLS: Record<MenuPage, UrlMethodPair> = {
    home: HomepageController.show(),
    products: ShopController.index(),
    categories: CategoryController.index(),
    brands: BrandController.index(),
    wishlist: WishlistController.show(),
    order_tracking: TrackOrderController.create(),
    refund_policy: RefundPolicyController.show(),
    privacy_policy: PrivacyPolicyController.show(),
    terms_of_service: TermsOfServiceController.show(),
};

function getLinkPreview(item: MenuItem): string {
    if (item.link_type === 'brand' && item.brand) {
        return BrandProductController.show(item.brand.url_handle).url;
    }
    if (item.link_type === 'category' && item.category) {
        return CategoryProductController.show(item.category.url_handle).url;
    }
    if (item.link_type === 'page' && item.page) {
        return PAGE_URLS[item.page].url;
    }
    return item.url || '#';
}

function getLinkIcon(linkType: LinkType) {
    switch (linkType) {
        case 'brand':
            return <TagIcon className="size-5" />;
        case 'category':
            return <ListTreeIcon className="size-5 rtl:-scale-x-100" />;
        case 'page':
            return <FileTextIcon className="size-5" />;
        default:
            return <ExternalLinkIcon className="size-5 rtl:-scale-x-100" />;
    }
}

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.2,
};

function DragPreview({ item }: { item: MenuItem }) {
    return (
        <div className="flex items-center gap-3 rounded-md border bg-popover p-3">
            <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
                {getLinkIcon(item.link_type)}
            </div>
            <div className="space-y-0.5">
                <p className="max-w-40 truncate text-sm font-medium">{getTranslation(item.label, 'Menu Item')}</p>
                <p className="text-xs text-muted-foreground">{getLinkPreview(item)}</p>
            </div>
        </div>
    );
}

export interface MenuItemRowProps {
    item: MenuItem;
    depth: number;
    index: number;
    disableAnimation?: boolean;
}

export function MenuItemRow({ item, depth, index, disableAnimation = false }: MenuItemRowProps) {
    const rowRef = useRef<HTMLDivElement>(null);
    const { confirm } = useConfirm();
    const { hasPermission } = usePermissions();
    const { reloadIframe } = useStorefrontBuilder();
    const canUpdate = hasPermission(Permission.StorefrontUpdate);
    const { isDragging, instruction } = useDragReorderItem<HTMLDivElement, Instruction>({
        ref: rowRef,
        enabled: canUpdate && !disableAnimation,
        getInitialData: () => ({ id: item.id, depth, index, parentId: item.parent_id }),
        getData: ({ input, element }) => {
            const mode: ItemMode = depth < 3 ? 'expanded' : 'standard';
            const blockedInstructions: Instruction['type'][] = depth >= 3 ? ['make-child'] : [];

            return attachInstruction(
                { id: item.id, depth, index, parentId: item.parent_id },
                { input, element, currentLevel: depth, indentPerLevel: 32, mode, block: blockedInstructions },
            );
        },
        extractInstruction,
        canDrop: ({ source }) => source.data.id !== item.id,
        renderPreview: () => <DragPreview item={item} />,
    });

    const handleDelete = () => {
        const childrenCount = item.children?.length || 0;
        const description =
            childrenCount > 0
                ? transChoice(
                      'Are you sure you want to delete ":name"? This will also delete :count child menu item.|Are you sure you want to delete ":name"? This will also delete :count child menu items.',
                      childrenCount,
                      { name: getTranslation(item.label, __('Menu item')), count: childrenCount },
                  )
                : __('This will permanently delete this menu item.');

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description,
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(MenuItemController.destroy(item), {
                        preserveScroll: true,
                        onSuccess: () => reloadIframe(),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    const handleToggleActive = () => {
        router.patch(
            MenuItemController.update(item),
            {
                is_active: !item.is_active,
            },
            {
                preserveScroll: true,
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <m.div layout={!disableAnimation} transition={animationTransition} className="relative">
            <m.div
                layout={!disableAnimation}
                ref={rowRef}
                transition={animationTransition}
                className={cn(
                    'group/item relative flex items-center justify-between gap-2 rounded-lg border bg-background p-3',
                    'transition-all hover:bg-muted/50',
                    (!item.is_active || isDragging) && 'opacity-60 hover:bg-background',
                    instruction?.type === 'make-child' && 'bg-muted/50 ring-2 ring-primary/20',
                    canUpdate && !disableAnimation && 'cursor-grab active:cursor-grabbing',
                )}
            >
                <div className="flex items-center justify-center gap-2">
                    {!disableAnimation && (
                        <Can permission={Permission.StorefrontUpdate}>
                            <GripVerticalIcon className="size-4 text-muted-foreground" />
                        </Can>
                    )}
                    <div className="flex flex-1 items-center justify-center gap-3">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            {getLinkIcon(item.link_type)}
                        </div>
                        <p className="text-sm font-medium">{getTranslation(item.label, 'Menu Item')}</p>
                    </div>
                </div>
                <HoverActions>
                    <Can permission={Permission.StorefrontUpdate}>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={item.is_active ? 'Hide menu item' : 'Show menu item'}
                            onClick={handleToggleActive}
                        >
                            {item.is_active ? <EyeIcon /> : <EyeOffIcon />}
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={__('Edit menu item')}
                            asChild
                        >
                            <Link href={MenuItemController.edit(item)}>
                                <PencilIcon />
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={__('Delete menu item')}
                            onClick={handleDelete}
                        >
                            <Trash2Icon />
                        </Button>
                    </Can>
                </HoverActions>

                {instruction?.type === 'reorder-above' && <DropIndicator edge="top" />}

                {instruction?.type === 'reorder-below' && <DropIndicator edge="bottom" />}

                {instruction?.type === 'reparent' && <DropIndicator edge="top" />}
            </m.div>
        </m.div>
    );
}
