import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import { BrandPicker, type SelectableBrand } from '@/components/admin/brand-picker';
import { CategoryPicker, type SelectableItem as SelectableCategory } from '@/components/admin/category-picker';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { HoverActions } from '@/components/admin/hover-actions';
import { ProductPicker, type SelectableItem as SelectableProduct } from '@/components/admin/product-picker';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { Thumbnail, ThumbnailRatio } from '@/components/admin/thumbnail';
import { AdaptiveSelect, type AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { ProductSource, ProductSourceSettings } from '@/types';

import { EmptyPlaceholder } from './empty-placeholder';
import { SectionLimitField } from './section-limit-field';

const productSourceOptions: AdaptiveSelectOption[] = [
    { value: 'latest', label: __('Latest products') },
    { value: 'featured', label: __('Hand-picked products') },
    { value: 'category', label: __('Products from a category') },
    { value: 'brand', label: __('Products from a brand') },
    { value: 'recently_viewed', label: __('Recently viewed products') },
];

interface ProductSourceFieldsProps {
    prefix?: string;
    settings?: ProductSourceSettings;
    errors: Record<string, string>;
    limitMax?: number;
}

export function ProductSourceFields({
    prefix = 'settings',
    settings,
    errors,
    limitMax = 50,
}: ProductSourceFieldsProps) {
    const [productSource, setProductSource] = useState<ProductSource>(settings?.product_source ?? 'latest');
    const [selectedCategory, setSelectedCategory] = useState<SelectableCategory | null>(settings?.category ?? null);
    const [selectedBrand, setSelectedBrand] = useState<SelectableBrand | null>(settings?.brand ?? null);
    const [selectedProducts, setSelectedProducts] = useState<SelectableProduct[]>(settings?.products ?? []);
    const [categoryPickerOpen, setCategoryPickerOpen] = useState(false);
    const [productPickerOpen, setProductPickerOpen] = useState(false);
    const { formatMoney } = useFormatMoney();

    const idBase = prefix.replace(/\./g, '-');

    return (
        <>
            <FormDirtySignal signal={`${prefix}:${productSource}:${selectedProducts.map((p) => p.id).join(',')}`} />
            <Field>
                <FieldLabel htmlFor={`${idBase}-product-source`}>{__('Product source')}</FieldLabel>
                <AdaptiveSelect
                    id={`${idBase}-product-source`}
                    name={`${prefix}.product_source`}
                    value={productSource}
                    onValueChange={(value) => setProductSource(value as ProductSource)}
                    options={productSourceOptions}
                    className="w-56"
                />
                <FieldError>{errors[`${prefix}.product_source`]}</FieldError>
            </Field>

            {productSource === 'category' && (
                <Field>
                    <FieldLabel htmlFor={`${idBase}-category`}>{__('Category')}</FieldLabel>
                    <ResourcePickerTrigger
                        id={`${idBase}-category`}
                        name={`${prefix}.category_id`}
                        value={selectedCategory?.id}
                        label={getTranslation(selectedCategory?.name)}
                        placeholder={__('Select a category')}
                        onOpen={() => setCategoryPickerOpen(true)}
                        onRemove={() => setSelectedCategory(null)}
                    />
                    <CategoryPicker
                        open={categoryPickerOpen}
                        onOpenChange={setCategoryPickerOpen}
                        selectedItems={selectedCategory ? [selectedCategory] : []}
                        onSelectionChange={setSelectedCategory}
                    />
                    <FieldError>{errors[`${prefix}.category_id`]}</FieldError>
                </Field>
            )}

            {productSource === 'brand' && (
                <Field>
                    <FieldLabel htmlFor={`${idBase}-brand`}>{__('Brand')}</FieldLabel>
                    <ResourcePickerTrigger
                        id={`${idBase}-brand`}
                        name={`${prefix}.brand_id`}
                        value={selectedBrand?.id}
                        label={getTranslation(selectedBrand?.name)}
                        placeholder={__('Select a brand')}
                        onOpen={() => setProductPickerOpen(true)}
                        onRemove={() => setSelectedBrand(null)}
                    />
                    <BrandPicker
                        open={productPickerOpen}
                        onOpenChange={setProductPickerOpen}
                        selectedItems={selectedBrand ? [selectedBrand] : []}
                        onSelectionChange={setSelectedBrand}
                    />
                    <FieldError>{errors[`${prefix}.brand_id`]}</FieldError>
                </Field>
            )}

            {productSource === 'featured' && (
                <Field>
                    <FieldLabel>
                        {__('Products')} {selectedProducts.length > 0 && `(${selectedProducts.length})`}
                    </FieldLabel>

                    {selectedProducts.length === 0 ? (
                        <EmptyPlaceholder
                            title={__('No products')}
                            description={__('Add products to display on your storefront.')}
                            action={
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setProductPickerOpen(true)}
                                >
                                    <PlusIcon />
                                    {__('Add products')}
                                </Button>
                            }
                        />
                    ) : (
                        <ThumbnailRatio media={selectedProducts.map((product) => product.featured_media)}>
                            <div className="space-y-2">
                                {selectedProducts.map((product, index) => (
                                    <div
                                        key={product.id}
                                        className={cn(
                                            'group/item relative flex items-center justify-between gap-2 rounded-lg border bg-background p-3',
                                            'transition-all hover:bg-muted/50',
                                        )}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Thumbnail
                                                src={mediaSmallThumb(product.featured_media)}
                                                alt={mediaAlt(product.featured_media, getTranslation(product.title))}
                                            />
                                            <div className="space-y-0.5">
                                                <p className="text-sm font-medium">{getTranslation(product.title)}</p>
                                                {product.price && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatMoney(product.price)}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <HoverActions>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={__('Remove product')}
                                                onClick={() =>
                                                    setSelectedProducts((prev) =>
                                                        prev.filter((p) => p.id !== product.id),
                                                    )
                                                }
                                            >
                                                <Trash2Icon />
                                            </Button>
                                        </HoverActions>
                                        <input
                                            type="hidden"
                                            name={`${prefix}.product_ids.${index}`}
                                            value={product.id}
                                        />
                                    </div>
                                ))}

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="w-fit"
                                    onClick={() => setProductPickerOpen(true)}
                                >
                                    <PlusIcon />
                                    {__('Add products')}
                                </Button>
                            </div>
                        </ThumbnailRatio>
                    )}
                    <FieldError>{errors[`${prefix}.product_ids`]}</FieldError>

                    <ProductPicker
                        open={productPickerOpen}
                        onOpenChange={setProductPickerOpen}
                        selectedItems={selectedProducts}
                        onSelectionChange={setSelectedProducts}
                        multiple
                    />
                </Field>
            )}

            {productSource !== 'featured' && (
                <SectionLimitField
                    label={(limit) => __('Number of products: :limit', { limit })}
                    name={`${prefix}.product_limit`}
                    defaultValue={settings?.product_limit ?? 8}
                    max={limitMax}
                    errors={errors}
                />
            )}
        </>
    );
}
