import { useMemo, useState } from 'react';

import ProductSearchController from '@/actions/App/Http/Controllers/Admin/ProductSearchController';
import { ResourcePicker, useApiList } from '@/components/admin/resource-picker';
import { Thumbnail, ThumbnailRatio } from '@/components/admin/thumbnail';
import { Button } from '@/components/ui/button';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __, transChoice } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { Product, ProductVariant } from '@/types';

type SelectableProduct = Pick<Product, 'id' | 'type' | 'title' | 'price' | 'price_range' | 'featured_media'>;
type SelectableVariant = Pick<ProductVariant, 'id' | 'product_id' | 'title' | 'price' | 'media' | 'options'>;

export type SelectableItem = SelectableProduct & { variant?: SelectableVariant };

interface ProductPickerProps<T extends boolean = false> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    selectedItems?: SelectableItem[];
    onSelectionChange?: T extends true
        ? (selection: SelectableItem[]) => void
        : (selection: SelectableItem | null) => void;
    title?: string;
    description?: string;
    withVariants?: boolean;
    multiple?: T;
}

export function ProductPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    withVariants = false,
    multiple = false as T,
}: ProductPickerProps<T>) {
    const { formatMoney } = useFormatMoney();
    const [selection, setSelection] = useState<SelectableItem[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const [prevOpen, setPrevOpen] = useState(open);
    const {
        items: products,
        loading,
        hasMore,
        handleLoadMore,
        searchQuery,
        setSearchQuery,
    } = useApiList<Product>(open, (query, page) => {
        if (showSelectedOnly) return;
        return ProductSearchController({
            mergeQuery: { query, page, with_variants: withVariants },
        });
    });

    if (open && !prevOpen) {
        setPrevOpen(true);
        setSelection(selectedItems);
        setShowSelectedOnly(false);
    } else if (!open && prevOpen) {
        setPrevOpen(false);
    }

    const isItemSelected = (productId: number, variantId?: string) => {
        return selection.some((item) => {
            if (item.id !== productId) return false;

            return variantId ? item.variant?.id === variantId : !item.variant;
        });
    };

    const getSelectedVariants = (productId: number) => {
        return selection.filter((item) => item.id === productId && item.variant);
    };

    const removeItemFromSelection = (productId: number, variantId?: string) => {
        return selection.filter((item) => {
            if (variantId) {
                return item.id !== productId || item.variant?.id !== variantId;
            }
            return item.id !== productId;
        });
    };

    const addMultipleItemsToSelection = (newItems: SelectableItem[], productId: number) => {
        const withoutThisProduct = selection.filter((item) => item.id !== productId);
        if (multiple) {
            return [...withoutThisProduct, ...newItems];
        }
        return newItems;
    };

    const handleToggleProduct = (product: Product) => {
        const hasVariants = product.variants && product.variants.length > 0;

        if (withVariants && hasVariants) {
            const selectedVariants = getSelectedVariants(product.id);
            const allVariantsSelected = selectedVariants.length === (product.variants?.length ?? 0);

            let newSelection: SelectableItem[];

            if (allVariantsSelected) {
                newSelection = removeItemFromSelection(product.id);
            } else {
                const allVariantItems: SelectableItem[] = (product.variants ?? []).map((variant) => ({
                    id: product.id,
                    type: product.type,
                    title: product.title,
                    price: product.price,
                    price_range: product.price_range ?? null,
                    featured_media: product.featured_media ?? null,
                    variant,
                }));
                newSelection = addMultipleItemsToSelection(allVariantItems, product.id);
            }

            setSelection(newSelection);
        } else {
            const isSelected = isItemSelected(product.id);
            let newSelection: SelectableItem[];

            if (isSelected) {
                newSelection = removeItemFromSelection(product.id);
            } else {
                const newItem: SelectableItem = {
                    id: product.id,
                    type: product.type,
                    title: product.title,
                    price: product.price,
                    price_range: product.price_range ?? null,
                    featured_media: product.featured_media ?? null,
                };
                newSelection = multiple ? [...selection, newItem] : [newItem];
            }

            setSelection(newSelection);
        }
    };

    const handleToggleVariant = (product: Product, variant: ProductVariant) => {
        const isSelected = isItemSelected(product.id, variant.id);
        let newSelection: SelectableItem[];

        if (isSelected) {
            newSelection = removeItemFromSelection(product.id, variant.id);
        } else {
            const newItem: SelectableItem = {
                id: product.id,
                title: product.title,
                price: product.price,
                price_range: product.price_range ?? null,
                featured_media: product.featured_media ?? null,
                type: product.type,
                variant,
            };

            if (multiple) {
                const withoutProduct = selection.filter((item) => !(item.id === product.id && !item.variant));
                newSelection = [...withoutProduct, newItem];
            } else {
                newSelection = [newItem];
            }
        }

        setSelection(newSelection);
    };

    const handleConfirm = () => {
        if (multiple) {
            (onSelectionChange as (selection: SelectableItem[]) => void)?.(selection);
        } else {
            (onSelectionChange as (selection: SelectableItem | null) => void)?.(selection[0] ?? null);
        }

        onOpenChange?.(false);
    };

    const filteredSelectedItems = useMemo(() => {
        if (!showSelectedOnly) return [];

        return searchQuery
            ? selection.filter((item) =>
                  (getTranslation(item.variant?.title) || getTranslation(item.title))
                      .toLowerCase()
                      .includes(searchQuery.toLowerCase()),
              )
            : selection;
    }, [selection, searchQuery, showSelectedOnly]);

    const selectedProductsMap = useMemo(() => {
        const map = new Map<number, SelectableItem[]>();
        filteredSelectedItems.forEach((item) => {
            if (!map.has(item.id)) {
                map.set(item.id, []);
            }
            map.get(item.id)!.push(item);
        });
        return map;
    }, [filteredSelectedItems]);

    return (
        <ThumbnailRatio media={products.map((product) => product.featured_media)}>
            <ResourcePicker
                open={open}
                onOpenChange={onOpenChange}
                title={title ?? (multiple ? __('Select products') : __('Select a product'))}
                description={description}
                searchQuery={searchQuery}
                onSearchChange={setSearchQuery}
                loading={loading}
                selectionCount={selection.length}
                onConfirm={handleConfirm}
                showSelectedOnly={showSelectedOnly}
                onToggleShowSelectedOnly={() => setShowSelectedOnly((prev) => !prev)}
                multiple={multiple}
            >
                {showSelectedOnly ? (
                    filteredSelectedItems.length === 0 ? (
                        <ResourcePicker.EmptyState />
                    ) : (
                        <>
                            {Array.from(selectedProductsMap.entries()).map(([productId, items]) => {
                                const firstItem = items[0];
                                const hasVariants = items.some((item) => item.variant);

                                if (!hasVariants) {
                                    return (
                                        <ResourcePicker.Item
                                            key={productId}
                                            leading={
                                                <Thumbnail
                                                    src={mediaSmallThumb(firstItem.featured_media)}
                                                    alt={mediaAlt(
                                                        firstItem.featured_media,
                                                        getTranslation(firstItem.title),
                                                    )}
                                                />
                                            }
                                            title={getTranslation(firstItem.title)}
                                            subtitle={
                                                firstItem.price != null
                                                    ? formatMoney(firstItem.price)
                                                    : firstItem.price_range
                                                      ? `${formatMoney(firstItem.price_range[0])} – ${formatMoney(firstItem.price_range[1])}`
                                                      : undefined
                                            }
                                            checked={true}
                                            onClick={() =>
                                                handleToggleProduct({
                                                    id: firstItem.id,
                                                    title: firstItem.title,
                                                    price: firstItem.price,
                                                    featured_media: firstItem.featured_media,
                                                } as Product)
                                            }
                                        />
                                    );
                                } else {
                                    return items.map((item) => (
                                        <ResourcePicker.Item
                                            key={`${item.id}-${item.variant?.id}`}
                                            leading={
                                                <Thumbnail
                                                    src={mediaSmallThumb(item.variant?.media ?? item.featured_media)}
                                                    alt={mediaAlt(
                                                        item.variant?.media ?? item.featured_media,
                                                        getTranslation(item.variant?.title) ||
                                                            getTranslation(item.title),
                                                    )}
                                                />
                                            }
                                            title={getTranslation(item.variant?.title) || getTranslation(item.title)}
                                            subtitle={formatMoney(item.variant?.price ?? item.price!)}
                                            checked={true}
                                            onClick={() =>
                                                item.variant &&
                                                handleToggleVariant(
                                                    {
                                                        id: item.id,
                                                        title: item.title,
                                                        price: item.price,
                                                        featured_media: item.featured_media,
                                                    } as Product,
                                                    item.variant as ProductVariant,
                                                )
                                            }
                                        />
                                    ));
                                }
                            })}
                        </>
                    )
                ) : loading && products.length === 0 ? (
                    <ResourcePicker.Skeleton />
                ) : products.length === 0 ? (
                    <ResourcePicker.EmptyState />
                ) : (
                    <>
                        {products.map((product) => (
                            <ProductItem
                                key={product.id}
                                product={product}
                                onToggleProduct={handleToggleProduct}
                                onToggleVariant={handleToggleVariant}
                                withVariants={withVariants}
                                multiple={multiple}
                                isItemSelected={isItemSelected}
                                getSelectedVariants={getSelectedVariants}
                            />
                        ))}

                        {hasMore && (
                            <div className="mb-4 text-center">
                                <Button variant="outline" size="sm" onClick={handleLoadMore} disabled={loading}>
                                    {loading ? __('Loading...') : __('Load more')}
                                </Button>
                            </div>
                        )}
                    </>
                )}
            </ResourcePicker>
        </ThumbnailRatio>
    );
}

interface ProductItemProps {
    product: Product;
    onToggleProduct: (product: Product) => void;
    onToggleVariant: (product: Product, variant: ProductVariant) => void;
    withVariants: boolean;
    multiple: boolean;
    isItemSelected: (productId: number, variantId?: string) => boolean;
    getSelectedVariants: (productId: number) => SelectableItem[];
}

function ProductItem({
    product,
    onToggleProduct,
    onToggleVariant,
    withVariants,
    multiple,
    isItemSelected,
    getSelectedVariants,
}: ProductItemProps) {
    const { formatMoney } = useFormatMoney();

    const isProductSelected = isItemSelected(product.id);
    const selectedVariants = getSelectedVariants(product.id);
    const hasVariants = product.variants && product.variants.length > 0;

    const checkboxState = useMemo<boolean | 'indeterminate'>(() => {
        if (!hasVariants || !withVariants) {
            return isProductSelected;
        }

        const variantCount = product.variants?.length ?? 0;
        const selectedVariantCount = selectedVariants.length;

        if (selectedVariantCount === 0) {
            return false;
        } else if (selectedVariantCount === variantCount) {
            return true;
        } else {
            return 'indeterminate';
        }
    }, [hasVariants, withVariants, isProductSelected, product.variants, selectedVariants.length]);

    return (
        <>
            <ResourcePicker.Item
                leading={
                    <Thumbnail
                        src={mediaSmallThumb(product.featured_media)}
                        alt={mediaAlt(product.featured_media, getTranslation(product.title))}
                    />
                }
                title={getTranslation(product.title)}
                subtitle={
                    withVariants && hasVariants
                        ? transChoice(':count variant|:count variants', product.variants?.length ?? 0)
                        : product.price != null
                          ? formatMoney(product.price)
                          : product.price_range
                            ? `${formatMoney(product.price_range[0])} – ${formatMoney(product.price_range[1])}`
                            : undefined
                }
                checked={checkboxState}
                onClick={() => onToggleProduct(product)}
                disabled={!multiple && withVariants && hasVariants}
            />

            <div className="ms-4 space-y-4 border-s">
                {withVariants &&
                    hasVariants &&
                    product.variants?.map((variant) => (
                        <ResourcePicker.Item
                            key={variant.id}
                            leading={
                                <Thumbnail
                                    src={mediaSmallThumb(variant.media)}
                                    alt={mediaAlt(variant.media, getTranslation(variant.title))}
                                />
                            }
                            title={getTranslation(variant.title)}
                            subtitle={formatMoney(variant.price!)}
                            checked={isItemSelected(product.id, variant.id)}
                            onClick={() => onToggleVariant(product, variant)}
                            className="ms-6"
                        />
                    ))}
            </div>
        </>
    );
}
