import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { extractInstruction, type Instruction } from '@atlaskit/pragmatic-drag-and-drop-hitbox/tree-item';
import type { FormDataConvertible } from '@inertiajs/core';
import { Link, router } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import * as MenuItemController from '@/actions/App/Http/Controllers/Admin/MenuItemController';
import * as MenuItemReorderController from '@/actions/App/Http/Controllers/Admin/MenuItemReorderController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontHeaderController from '@/actions/App/Http/Controllers/Admin/StorefrontHeaderController';
import {
    BrowseCategoriesFields,
    type BrowseCategoryData,
    type BrowseCategoryPayload,
} from '@/components/admin/storefront/browse-categories-fields';
import { EmptyPlaceholder } from '@/components/admin/storefront/empty-placeholder';
import { MenuItemRow } from '@/components/admin/storefront/menu-item-row';
import { SwitchSetting } from '@/components/admin/storefront/switch-setting';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { SearchInput } from '@/components/ui/search-input';
import { Separator } from '@/components/ui/separator';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { MenuItem } from '@/types';

interface FlatMenuItem {
    item: MenuItem;
    depth: number;
    index: number;
    parentId: number | null;
}

interface HeaderSettings {
    sticky: boolean;
}

interface HeaderProps {
    menuItems: MenuItem[];
    browseCategories: BrowseCategoryData[];
    settings: HeaderSettings;
}

function getMaxSubtreeDepth(menuItem: MenuItem): number {
    if (!menuItem.children || menuItem.children.length === 0) {
        return 1;
    }

    let maxChildDepth = 0;
    for (const child of menuItem.children) {
        const childDepth = getMaxSubtreeDepth(child);
        if (childDepth > maxChildDepth) {
            maxChildDepth = childDepth;
        }
    }

    return 1 + maxChildDepth;
}

export default function Header({ menuItems, browseCategories, settings }: HeaderProps) {
    const [headerSettings, setHeaderSettings] = useState<HeaderSettings>(settings);
    const [searchQuery, setSearchQuery] = useState('');
    const { reloadIframe } = useStorefrontBuilder();

    const filteredMenuItems = useMemo(() => {
        if (!searchQuery.trim()) {
            return menuItems;
        }

        const query = searchQuery.toLowerCase();

        const filterItems = (items: MenuItem[]): MenuItem[] => {
            return items.reduce<MenuItem[]>((acc, item) => {
                const labelMatches = Object.values(item.label).some((translation) =>
                    String(translation).toLowerCase().includes(query),
                );

                const filteredChildren = item.children ? filterItems(item.children) : [];

                if (labelMatches || filteredChildren.length > 0) {
                    acc.push({
                        ...item,
                        children: filteredChildren.length > 0 ? filteredChildren : item.children,
                    });
                }

                return acc;
            }, []);
        };

        return filterItems(menuItems);
    }, [menuItems, searchQuery]);

    const flatItems = useMemo(() => {
        const result: FlatMenuItem[] = [];

        function traverse(items: MenuItem[], depth: number, parentId: number | null) {
            items.forEach((item, index) => {
                result.push({ item, depth, index, parentId });
                if (item.children && item.children.length > 0) {
                    traverse(item.children, depth + 1, item.id);
                }
            });
        }

        traverse(menuItems, 1, null);
        return result;
    }, [menuItems]);

    const findFlatItem = useCallback(
        (id: number): FlatMenuItem | undefined => {
            return flatItems.find((f) => f.item.id === id);
        },
        [flatItems],
    );

    const getDescendantIds = useCallback(
        (menuItemId: number): number[] => {
            const flatItem = findFlatItem(menuItemId);
            if (!flatItem || !flatItem.item.children || flatItem.item.children.length === 0) {
                return [];
            }

            const descendants: number[] = [];
            const collectDescendants = (children: MenuItem[]) => {
                children.forEach((child) => {
                    descendants.push(child.id);
                    if (child.children && child.children.length > 0) {
                        collectDescendants(child.children);
                    }
                });
            };

            collectDescendants(flatItem.item.children);
            return descendants;
        },
        [findFlatItem],
    );

    const getSiblings = useCallback(
        (parentId: number | null): MenuItem[] => {
            if (parentId === null) {
                return menuItems;
            }
            const flatItem = findFlatItem(parentId);
            return flatItem?.item.children || [];
        },
        [menuItems, findFlatItem],
    );

    const isValidDrop = useCallback(
        (draggedId: number, targetId: number, operation: string): boolean => {
            if (draggedId === targetId) return false;

            const descendantIds = getDescendantIds(draggedId);
            if (descendantIds.includes(targetId)) return false;

            const draggedFlat = findFlatItem(draggedId);
            const targetFlat = findFlatItem(targetId);

            if (!draggedFlat || !targetFlat) return false;

            if (operation === 'make-child') {
                const targetDepth = targetFlat.depth;
                const draggedSubtreeDepth = getMaxSubtreeDepth(draggedFlat.item);

                if (targetDepth + draggedSubtreeDepth > 3) {
                    return false;
                }
            }

            return true;
        },
        [getDescendantIds, findFlatItem],
    );

    useEffect(() => {
        return monitorForElements({
            onDrop({ location, source }) {
                if (!location.current.dropTargets.length) return;

                const draggedId = source.data.id as number;
                const target = location.current.dropTargets[0];
                const targetId = target.data.id as number;

                const draggedFlat = findFlatItem(draggedId);
                const targetFlat = findFlatItem(targetId);

                if (!draggedFlat || !targetFlat) return;

                const instruction: Instruction | null = extractInstruction(target.data);

                if (instruction === null) return;
                if (!isValidDrop(draggedId, targetId, instruction.type)) return;

                let newParentId: number | null;
                let newPosition: number;

                if (instruction.type === 'make-child') {
                    newParentId = targetId;
                    newPosition = targetFlat.item.children?.length || 0;
                } else if (instruction.type === 'reorder-above') {
                    newParentId = targetFlat.parentId;
                    const siblings = getSiblings(newParentId);
                    const targetIndex = siblings.findIndex((m) => m.id === targetId);

                    if (targetIndex === -1) return;

                    if (draggedFlat.parentId === newParentId) {
                        const draggedIndex = siblings.findIndex((m) => m.id === draggedId);
                        if (draggedIndex === -1) return;
                        newPosition = draggedIndex < targetIndex ? targetIndex - 1 : targetIndex;
                    } else {
                        newPosition = targetIndex;
                    }
                } else if (instruction.type === 'reorder-below') {
                    newParentId = targetFlat.parentId;
                    const siblings = getSiblings(newParentId);
                    const targetIndex = siblings.findIndex((m) => m.id === targetId);

                    if (targetIndex === -1) return;

                    if (draggedFlat.parentId === newParentId) {
                        const draggedIndex = siblings.findIndex((m) => m.id === draggedId);
                        if (draggedIndex === -1) return;
                        newPosition = draggedIndex < targetIndex ? targetIndex : targetIndex + 1;
                    } else {
                        newPosition = targetIndex + 1;
                    }
                } else if (instruction.type === 'reparent') {
                    const desiredLevel = instruction.desiredLevel as number;
                    if (desiredLevel === 1) {
                        newParentId = null;
                    } else {
                        return;
                    }

                    const siblings = getSiblings(newParentId);
                    const targetIndex = siblings.findIndex((m) => m.id === targetId);

                    if (targetIndex === -1) {
                        newPosition = siblings.length;
                    } else {
                        newPosition = targetIndex + 1;
                    }
                } else if (instruction.type === 'instruction-blocked') {
                    return;
                } else {
                    return;
                }

                router.patch(
                    MenuItemReorderController.update(draggedFlat.item),
                    {
                        parent_id: newParentId,
                        position: newPosition,
                    },
                    {
                        preserveScroll: true,
                        onSuccess: () => reloadIframe(),
                    },
                );
            },
        });
    }, [flatItems, findFlatItem, getSiblings, isValidDrop, reloadIframe]);

    const handleBrowseCategoriesChange = (payload: BrowseCategoryPayload[]) => {
        router.patch(
            StorefrontHeaderController.update(),
            { storefront_header_browse_categories: payload as unknown as FormDataConvertible },
            {
                preserveScroll: true,
                only: ['browseCategories'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    const updateSetting = (key: keyof HeaderSettings, value: boolean) => {
        setHeaderSettings((prev) => ({ ...prev, [key]: value }));

        const settingKeyMap: Record<keyof HeaderSettings, string> = {
            sticky: 'storefront_header_sticky',
        };

        router.patch(
            StorefrontHeaderController.update(),
            { [settingKeyMap[key]]: value },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    const addButton = (
        <Can permission={Permission.StorefrontUpdate}>
            <Button variant="outline" size="sm" asChild>
                <Link href={MenuItemController.create({ query: { location: 'header' } })}>
                    <PlusIcon className="size-4" />
                    {__('Add menu item')}
                </Link>
            </Button>
        </Can>
    );

    return (
        <div className="mb-6 space-y-6 p-4 text-sm">
            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Header options')}</h3>
                    <p className="text-muted-foreground">{__('Configure header behavior and navigation style')}</p>
                </div>

                <div className="space-y-4">
                    <SwitchSetting
                        label={__('Sticky header')}
                        description={__('Keep the header visible when scrolling down the page')}
                        checked={headerSettings.sticky}
                        onCheckedChange={(checked) => updateSetting('sticky', checked)}
                    />
                    <SwitchSetting
                        label={__('Language switcher')}
                        description={__('Show language switcher in header')}
                        checked={false}
                        pro
                    />
                    <SwitchSetting
                        label={__('Currency switcher')}
                        description={__('Show currency switcher in header')}
                        checked={false}
                        pro
                    />
                </div>
            </div>

            <Separator />

            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Browse categories')}</h3>
                    <p className="text-muted-foreground">
                        {__('Add categories to the Browse categories menu. Each category shows its subcategories.')}
                    </p>
                </div>

                <BrowseCategoriesFields browseCategories={browseCategories} onSave={handleBrowseCategoriesChange} />
            </div>

            <Separator />

            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Navigation menu')}</h3>
                    <p className="text-muted-foreground">
                        {__('Add and organize navigation links that appear in your header')}
                    </p>
                </div>

                {menuItems.length > 0 && (
                    <SearchInput value={searchQuery} onChange={setSearchQuery} placeholder={__('Search...')} />
                )}

                {menuItems.length === 0 ? (
                    <EmptyPlaceholder
                        title={__('No menu items')}
                        description={__('Get started by adding your first menu item.')}
                        action={addButton}
                    />
                ) : filteredMenuItems.length === 0 ? (
                    <div className="py-8 text-center text-muted-foreground">{__('No items found.')}</div>
                ) : (
                    <div className="space-y-2">
                        {filteredMenuItems.map((item, rootIndex) => (
                            <div key={item.id}>
                                <MenuItemRow item={item} depth={1} index={rootIndex} disableAnimation={!!searchQuery} />

                                {item.children && item.children.length > 0 && (
                                    <div className="ms-8 mt-2 space-y-2">
                                        {item.children.map((child, childIndex) => (
                                            <div key={child.id}>
                                                <MenuItemRow
                                                    item={child}
                                                    depth={2}
                                                    index={childIndex}
                                                    disableAnimation={!!searchQuery}
                                                />

                                                {child.children && child.children.length > 0 && (
                                                    <div className="ms-8 mt-2 space-y-2">
                                                        {child.children.map((grandchild, grandchildIndex) => (
                                                            <MenuItemRow
                                                                key={grandchild.id}
                                                                item={grandchild}
                                                                depth={3}
                                                                index={grandchildIndex}
                                                                disableAnimation={!!searchQuery}
                                                            />
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}

                        {addButton}
                    </div>
                )}
            </div>
        </div>
    );
}

Header.layout = {
    title: __('Header'),
    backHref: StorefrontController.index(),
};
