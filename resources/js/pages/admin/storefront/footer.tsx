import { monitorForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { extractInstruction, type Instruction } from '@atlaskit/pragmatic-drag-and-drop-hitbox/tree-item';
import { Link, router } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import * as MenuItemController from '@/actions/App/Http/Controllers/Admin/MenuItemController';
import * as MenuItemReorderController from '@/actions/App/Http/Controllers/Admin/MenuItemReorderController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontFooterController from '@/actions/App/Http/Controllers/Admin/StorefrontFooterController';
import { EmptyPlaceholder } from '@/components/admin/storefront/empty-placeholder';
import { MenuItemRow } from '@/components/admin/storefront/menu-item-row';
import { SwitchSetting } from '@/components/admin/storefront/switch-setting';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { CheckboxCard } from '@/components/ui/checkbox-card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioCard, RadioCardGroup } from '@/components/ui/radio-card';
import { SearchInput } from '@/components/ui/search-input';
import { Separator } from '@/components/ui/separator';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { FooterSettings, MenuItem, PaymentMethodPreset, PaymentMethodName } from '@/types';

const paymentPresets: Array<{
    value: PaymentMethodPreset;
    label: string;
    description: string;
    methods: PaymentMethodName[];
}> = [
    {
        value: 'all',
        label: __('All payment methods'),
        description: __('Show all available payment options'),
        methods: [
            'visa',
            'mastercard',
            'amex',
            'discover',
            'jcb',
            'unionpay',
            'mada',
            'paypal',
            'apple_pay',
            'google_pay',
            'upi',
            'ideal',
        ],
    },
    {
        value: 'credit_cards',
        label: __('Credit cards only'),
        description: __('Visa, Mastercard, Amex, and other card networks'),
        methods: ['visa', 'mastercard', 'amex', 'discover', 'jcb', 'unionpay', 'mada'],
    },
    {
        value: 'digital_wallets',
        label: __('Digital wallets only'),
        description: __('PayPal, Apple Pay, Google Pay, and other digital payments'),
        methods: ['paypal', 'apple_pay', 'google_pay', 'upi', 'ideal'],
    },
    {
        value: 'custom',
        label: __('Custom selection'),
        description: __('Choose specific payment methods'),
        methods: [],
    },
];

const allPaymentMethods: Array<{ value: PaymentMethodName; label: string }> = [
    { value: 'visa', label: 'Visa' },
    { value: 'mastercard', label: 'Mastercard' },
    { value: 'amex', label: 'Amex' },
    { value: 'discover', label: 'Discover' },
    { value: 'jcb', label: 'JCB' },
    { value: 'unionpay', label: 'UnionPay' },
    { value: 'mada', label: 'mada' },
    { value: 'paypal', label: 'PayPal' },
    { value: 'apple_pay', label: 'Apple Pay' },
    { value: 'google_pay', label: 'Google Pay' },
    { value: 'upi', label: 'UPI' },
    { value: 'ideal', label: 'iDEAL' },
];

interface FlatMenuItem {
    item: MenuItem;
    depth: number;
    index: number;
    parentId: number | null;
}

interface FooterProps {
    menuItems: MenuItem[];
    settings: FooterSettings;
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

export default function Footer({ menuItems, settings }: FooterProps) {
    const [footerSettings, setFooterSettings] = useState<FooterSettings>(settings);
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

                if (targetDepth + draggedSubtreeDepth > 2) {
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

    const updateSetting = (key: keyof FooterSettings, value: boolean | string | string[] | null) => {
        setFooterSettings((prev) => ({ ...prev, [key]: value }));

        const settingKey = `storefront_footer_${key}`;
        router.patch(
            StorefrontFooterController.update(),
            { [settingKey]: value },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    const handlePresetChange = (preset: PaymentMethodPreset) => {
        const selectedPreset = paymentPresets.find((p) => p.value === preset);
        if (!selectedPreset) return;

        const updates: Partial<FooterSettings> = {
            payment_method_preset: preset,
        };

        if (preset !== 'custom') {
            updates.payment_methods = selectedPreset.methods;
        }

        setFooterSettings((prev) => ({ ...prev, ...updates }));

        router.patch(
            StorefrontFooterController.update(),
            {
                storefront_footer_payment_method_preset: preset,
                ...(preset !== 'custom' && { storefront_footer_payment_methods: selectedPreset.methods }),
            },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    const handleCustomMethodToggle = (method: PaymentMethodName) => {
        const currentMethods = footerSettings.payment_methods as PaymentMethodName[];
        const newMethods = currentMethods.includes(method)
            ? currentMethods.filter((m) => m !== method)
            : [...currentMethods, method];

        updateSetting('payment_methods', newMethods);
    };

    const addButton = (
        <Can permission={Permission.StorefrontUpdate}>
            <Button variant="outline" size="sm" asChild>
                <Link href={MenuItemController.create({ query: { location: 'footer' } })}>
                    <PlusIcon className="size-4" />
                    {__('Add link')}
                </Link>
            </Button>
        </Can>
    );

    return (
        <div className="mb-6 space-y-6 p-4 text-sm">
            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Footer elements')}</h3>
                    <p className="text-muted-foreground">{__('Choose which elements to display in your footer')}</p>
                </div>

                <div className="space-y-4">
                    <SwitchSetting
                        label={__('Copyright text')}
                        description={__('Show the copyright notice in your footer')}
                        checked={footerSettings.show_copyright}
                        onCheckedChange={(checked) => updateSetting('show_copyright', checked)}
                    />
                    <SwitchSetting
                        label={__('Social media links')}
                        description={__('Show links to your social media profiles')}
                        checked={footerSettings.show_social_links}
                        onCheckedChange={(checked) => updateSetting('show_social_links', checked)}
                    />
                    <SwitchSetting
                        label={__('Payment badges')}
                        description={__('Display accepted payment method icons')}
                        checked={footerSettings.show_payment_badges}
                        onCheckedChange={(checked) => updateSetting('show_payment_badges', checked)}
                    />
                    <SwitchSetting
                        label={__('Powered by')}
                        description={__('Show the powered by attribution')}
                        checked
                        pro
                    />
                </div>
            </div>

            {footerSettings.show_copyright && (
                <>
                    <Separator />

                    <div className="space-y-4">
                        <div className="space-y-1">
                            <h3 className="font-medium">{__('Copyright text')}</h3>
                            <p className="text-muted-foreground">
                                {__('Add custom copyright notice to appear at the bottom of your footer')}
                            </p>
                        </div>

                        <Can
                            permission={Permission.StorefrontUpdate}
                            fallback={
                                <div className="space-y-2">
                                    <Label htmlFor="copyright_text" className="sr-only">
                                        {__('Copyright text')}
                                    </Label>
                                    <Input
                                        id="copyright_text"
                                        value={footerSettings.copyright_text || ''}
                                        placeholder={__('e.g., All rights reserved.')}
                                        disabled
                                    />
                                </div>
                            }
                        >
                            <div className="space-y-2">
                                <Label htmlFor="copyright_text" className="sr-only">
                                    {__('Copyright text')}
                                </Label>
                                <Input
                                    id="copyright_text"
                                    defaultValue={footerSettings.copyright_text || ''}
                                    placeholder={__('e.g., All rights reserved.')}
                                    onBlur={(e) => {
                                        const value = e.target.value.trim() || null;
                                        if (value !== footerSettings.copyright_text) {
                                            updateSetting('copyright_text', value);
                                        }
                                    }}
                                />
                            </div>
                        </Can>
                    </div>
                </>
            )}

            {footerSettings.show_payment_badges && (
                <>
                    <Separator />

                    <div className="space-y-4">
                        <div className="space-y-1">
                            <h3 id="payment-badges-heading" className="font-medium">
                                {__('Payment badges selection')}
                            </h3>
                            <p className="text-muted-foreground">
                                {__('Choose which payment method badges to display in your footer')}
                            </p>
                        </div>

                        <Can
                            permission={Permission.StorefrontUpdate}
                            fallback={
                                <div className="space-y-3 opacity-60">
                                    <RadioCardGroup
                                        value={footerSettings.payment_method_preset}
                                        readonly
                                        aria-labelledby="payment-badges-heading"
                                    >
                                        {paymentPresets.map((preset) => (
                                            <RadioCard
                                                key={preset.value}
                                                value={preset.value}
                                                label={preset.label}
                                                description={preset.description}
                                            />
                                        ))}
                                    </RadioCardGroup>
                                    {footerSettings.payment_method_preset === 'custom' && (
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {allPaymentMethods.map((method) => (
                                                <CheckboxCard
                                                    key={method.value}
                                                    id={`payment-method-${method.value}-disabled`}
                                                    label={method.label}
                                                    defaultChecked={(
                                                        footerSettings.payment_methods as PaymentMethodName[]
                                                    ).includes(method.value)}
                                                    disabled
                                                />
                                            ))}
                                        </div>
                                    )}
                                </div>
                            }
                        >
                            <div className="space-y-3">
                                <RadioCardGroup
                                    value={footerSettings.payment_method_preset}
                                    onValueChange={(next) => handlePresetChange(next as PaymentMethodPreset)}
                                    aria-labelledby="payment-badges-heading"
                                >
                                    {paymentPresets.map((preset) => (
                                        <RadioCard
                                            key={preset.value}
                                            value={preset.value}
                                            label={preset.label}
                                            description={preset.description}
                                        />
                                    ))}
                                </RadioCardGroup>

                                {footerSettings.payment_method_preset === 'custom' && (
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {allPaymentMethods.map((method) => (
                                            <CheckboxCard
                                                key={method.value}
                                                id={`payment-method-${method.value}`}
                                                label={method.label}
                                                defaultChecked={footerSettings.payment_methods.includes(method.value)}
                                                onCheckedChange={() => handleCustomMethodToggle(method.value)}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        </Can>
                    </div>
                </>
            )}

            <Separator />

            <div className="space-y-4">
                <div className="space-y-1">
                    <h3 className="font-medium">{__('Footer links')}</h3>
                    <p className="text-muted-foreground">{__('Add and organize links that appear in your footer')}</p>
                </div>

                {menuItems.length > 0 && (
                    <SearchInput value={searchQuery} onChange={setSearchQuery} placeholder={__('Search...')} />
                )}

                {menuItems.length === 0 ? (
                    <EmptyPlaceholder
                        title={__('No links')}
                        description={__('Add links to display in your footer.')}
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
                                            <MenuItemRow
                                                key={child.id}
                                                item={child}
                                                depth={2}
                                                index={childIndex}
                                                disableAnimation={!!searchQuery}
                                            />
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

Footer.layout = {
    title: __('Footer'),
    backHref: StorefrontController.index(),
};
