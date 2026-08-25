import { AlertCircleIcon } from 'lucide-react';
import { useCallback } from 'react';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { __ } from '@/lib/i18n';
import type { ProductVariant, VariantTabs } from '@/types';

import { VariantInventory } from './variant-inventory';
import { VariantMedia } from './variant-media';
import { VariantPricing } from './variant-pricing';
import { VariantShipping } from './variant-shipping';

const tabFields: Record<VariantTabs, string[]> = {
    pricing: ['price', 'compare_at_price', 'cost_per_item'],
    inventory: ['sku', 'barcode', 'stock'],
    shipping: ['weight', 'weight_unit', 'length', 'width', 'height', 'dimension_unit'],
    media: [],
};

interface VariantTabsProps {
    variants: ProductVariant[];
    errors: Record<string, string>;
    isDigital?: boolean;
    onVariantUpdate: (variantId: string, updates: Partial<ProductVariant>) => void;
    onAllVariantsUpdate: (updates: Partial<ProductVariant>) => void;
}

export function VariantTabs({
    variants,
    errors,
    isDigital = false,
    onVariantUpdate,
    onAllVariantsUpdate,
}: VariantTabsProps) {
    const variantTabHasErrors = useCallback(
        (tabName: VariantTabs): boolean => {
            return variants.some((_, index: number) => {
                return tabFields[tabName].some((field) => {
                    return !!errors[`variants.${index}.${field}`];
                });
            });
        },
        [variants, errors],
    );

    return (
        <Tabs defaultValue="pricing">
            <TabsList className="flex w-full flex-wrap">
                <TabsTrigger value="pricing" className="flex-1">
                    {__('Pricing')}
                    {variantTabHasErrors('pricing') && <AlertCircleIcon className="text-destructive" size={16} />}
                </TabsTrigger>

                <TabsTrigger value="inventory" className="flex-1">
                    {__('Inventory')}
                    {variantTabHasErrors('inventory') && <AlertCircleIcon className="text-destructive" size={16} />}
                </TabsTrigger>

                {!isDigital && (
                    <TabsTrigger value="shipping" className="flex-1">
                        {__('Shipping')}
                        {variantTabHasErrors('shipping') && <AlertCircleIcon className="text-destructive" size={16} />}
                    </TabsTrigger>
                )}

                <TabsTrigger value="media" className="flex-1">
                    {__('Media')}
                    {variantTabHasErrors('media') && <AlertCircleIcon className="text-destructive" size={16} />}
                </TabsTrigger>
            </TabsList>

            <TabsContent value="pricing" className="mt-4 data-[state=inactive]:hidden" forceMount>
                <VariantPricing
                    variants={variants}
                    errors={errors}
                    onVariantUpdate={onVariantUpdate}
                    onAllVariantsUpdate={onAllVariantsUpdate}
                />
            </TabsContent>
            <TabsContent value="inventory" className="mt-4 data-[state=inactive]:hidden" forceMount>
                <VariantInventory
                    variants={variants}
                    errors={errors}
                    onVariantUpdate={onVariantUpdate}
                    onAllVariantsUpdate={onAllVariantsUpdate}
                />
            </TabsContent>
            {!isDigital && (
                <TabsContent value="shipping" className="mt-4 data-[state=inactive]:hidden" forceMount>
                    <VariantShipping
                        variants={variants}
                        errors={errors}
                        onVariantUpdate={onVariantUpdate}
                        onAllVariantsUpdate={onAllVariantsUpdate}
                    />
                </TabsContent>
            )}
            <TabsContent value="media" className="mt-4 data-[state=inactive]:hidden" forceMount>
                <VariantMedia variants={variants} onVariantUpdate={onVariantUpdate} />
            </TabsContent>
        </Tabs>
    );
}
