import { usePage } from '@inertiajs/react';
import { PackageIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import { HoverActions } from '@/components/admin/hover-actions';
import { InfoPopover } from '@/components/admin/info-popover';
import { LineItemsTable } from '@/components/admin/line-items-list';
import { OrderLineItemRow } from '@/components/admin/order/order-line-item-row';
import { ProductPicker, type SelectableItem } from '@/components/admin/product-picker';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { QuantityInput } from '@/components/ui/quantity-input';
import { Separator } from '@/components/ui/separator';
import { multiply } from '@/lib/decimal';
import { __, transChoice } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { MediaDimensions } from '@/types/media';

export interface OrderItemForm {
    id: string;
    product_id: string;
    product_variant_id: string;
    product_title: string;
    variant_title: string | null;
    thumbnail_url: string | null;
    media?: MediaDimensions | null;
    quantity: string;
    unit_price: string;
    total_price: string;
    tax_amount: string;
    variant_options: Record<string, string>;
    requires_shipping: boolean;
    shipped_quantity: number;
}

interface OrderFormItemsProps {
    errors: Record<string, string>;
    items: OrderItemForm[];
    onItemsChange: (items: OrderItemForm[]) => void;
    exchangeRate?: string;
    currencyCode?: string;
    restockItemCount?: number;
    restock?: boolean;
    onRestockChange?: (value: boolean) => void;
}

export function OrderFormItems({
    errors,
    items,
    onItemsChange,
    exchangeRate,
    currencyCode,
    restockItemCount = 0,
    restock = true,
    onRestockChange,
}: OrderFormItemsProps) {
    const [showProductPicker, setShowProductPicker] = useState(false);
    const { baseCurrency } = usePage().props;
    const rate = parseFloat(exchangeRate ?? '1') || 1;

    const addProductsToOrder = (selectedItems: SelectableItem[]) => {
        const newItems = selectedItems.map((item) => selectedItemToForm(item, rate));
        const updatedItems = [...items, ...newItems];
        onItemsChange(updatedItems);
    };

    const removeOrderItem = (index: number) => {
        const updatedItems = items.filter((_, i) => i !== index);
        onItemsChange(updatedItems);
    };

    const updateOrderItem = (index: number, field: 'quantity' | 'unit_price', value: string) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };

        if (field === 'quantity' || field === 'unit_price') {
            const quantity = Number(newItems[index].quantity) || 0;
            const unitPrice = Number(newItems[index].unit_price) || 0;
            newItems[index].total_price = multiply(quantity, unitPrice);
        }

        onItemsChange(newItems);
    };

    return (
        <Card>
            {items.length > 0 && (
                <CardHeader className="max-sm:has-data-[slot=card-action]:grid-cols-1">
                    <CardTitle>{__('Order items')}</CardTitle>
                    <CardDescription>{__('Products included in this order')}</CardDescription>
                    <CardAction className="max-sm:col-start-1 max-sm:row-span-1 max-sm:row-start-3 max-sm:mt-1 max-sm:justify-self-start">
                        <Button type="button" variant="outline" onClick={() => setShowProductPicker(true)}>
                            <PlusIcon />
                            {__('Add products')}
                        </Button>
                    </CardAction>
                </CardHeader>
            )}

            <CardContent>
                {items.length === 0 ? (
                    <Empty>
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <PackageIcon />
                            </EmptyMedia>
                            <EmptyTitle>{__('No order items')}</EmptyTitle>
                            <EmptyDescription>{__('Add products to build the order.')}</EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button type="button" variant="outline" onClick={() => setShowProductPicker(true)}>
                                <PlusIcon />
                                {__('Add products')}
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <>
                        <LineItemsTable withActions>
                            <ThumbnailRatio media={items.map((item) => item.media)}>
                                {items.map((item, index) => {
                                    const quantityFloor = Math.max(1, item.shipped_quantity);
                                    const isShipped = item.shipped_quantity > 0;

                                    return (
                                        <OrderLineItemRow
                                            key={item.id || `${item.product_id}-${item.product_variant_id}-${index}`}
                                            thumbnail_url={item.thumbnail_url}
                                            title={item.product_title}
                                            variantTitle={item.variant_title}
                                            unitPrice={item.unit_price}
                                            totalPrice={item.total_price}
                                            currencyCode={currencyCode ?? baseCurrency}
                                            quantityControl={
                                                <QuantityInput
                                                    itemId={index}
                                                    name={`items.${index}.quantity`}
                                                    quantity={parseInt(item.quantity) || 1}
                                                    minQuantity={quantityFloor}
                                                    onQuantityChange={(_, delta) => {
                                                        const newQuantity = Math.max(
                                                            quantityFloor,
                                                            (parseInt(item.quantity) || 1) + delta,
                                                        );
                                                        updateOrderItem(index, 'quantity', String(newQuantity));
                                                    }}
                                                />
                                            }
                                            note={
                                                <>
                                                    {isShipped && (
                                                        <div className="flex items-center gap-1.5">
                                                            <Badge variant="secondary" className="font-normal">
                                                                {__(':count shipped', { count: item.shipped_quantity })}
                                                            </Badge>
                                                            <InfoPopover
                                                                text={__(
                                                                    'Shipped units cannot be removed or reduced below this, though you can still add more.',
                                                                )}
                                                            />
                                                        </div>
                                                    )}

                                                    <FieldError>{errors[`items.${index}.product_id`]}</FieldError>
                                                    <FieldError>{errors[`items.${index}.quantity`]}</FieldError>
                                                </>
                                            }
                                            trailing={
                                                <HoverActions>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon-md"
                                                        onClick={() => removeOrderItem(index)}
                                                        disabled={isShipped}
                                                    >
                                                        <Trash2Icon />
                                                    </Button>
                                                </HoverActions>
                                            }
                                        >
                                            {item.id && (
                                                <input type="hidden" name={`items.${index}.id`} value={item.id} />
                                            )}
                                            {item.product_id && (
                                                <input
                                                    type="hidden"
                                                    name={`items.${index}.product_id`}
                                                    value={item.product_id}
                                                />
                                            )}
                                            {item.product_variant_id && (
                                                <input
                                                    type="hidden"
                                                    name={`items.${index}.product_variant_id`}
                                                    value={item.product_variant_id}
                                                />
                                            )}
                                            {Object.entries(item.variant_options).map(([key, value]) => (
                                                <input
                                                    key={key}
                                                    type="hidden"
                                                    name={`items.${index}.variant_options.${key}`}
                                                    value={value}
                                                />
                                            ))}
                                        </OrderLineItemRow>
                                    );
                                })}
                            </ThumbnailRatio>
                        </LineItemsTable>
                        <FieldError>{errors.items}</FieldError>
                        {restockItemCount > 0 && (
                            <>
                                <Separator className="my-4" />
                                <Field orientation="horizontal">
                                    <Checkbox
                                        id="restock"
                                        checked={restock}
                                        onCheckedChange={(checked) => onRestockChange?.(checked === true)}
                                    />
                                    <FieldLabel htmlFor="restock" className="font-normal">
                                        {transChoice('Restock :count item|Restock :count items', restockItemCount)}
                                    </FieldLabel>
                                </Field>
                            </>
                        )}
                    </>
                )}
            </CardContent>

            <ProductPicker
                open={showProductPicker}
                onOpenChange={setShowProductPicker}
                onSelectionChange={addProductsToOrder}
                withVariants
                multiple
            />
        </Card>
    );
}

function selectedItemToForm(item: SelectableItem, exchangeRate: number): OrderItemForm {
    const convertedPrice = multiply(item.variant?.price || item.price || '0.0000', exchangeRate);
    const variantOptions = (item.variant?.options || []).reduce(
        (acc, option) => {
            acc[getTranslation(option.name)] = getTranslation(option.value);
            return acc;
        },
        {} as Record<string, string>,
    );

    return {
        id: '',
        product_id: String(item.id),
        product_variant_id: String(item.variant?.id ?? ''),
        product_title: getTranslation(item.title) || '',
        variant_title: item.variant ? getTranslation(item.variant.title) : null,
        thumbnail_url: mediaSmallThumb(item.variant?.media ?? item.featured_media),
        media: item.variant?.media ?? item.featured_media,
        quantity: '1',
        unit_price: convertedPrice,
        total_price: convertedPrice,
        tax_amount: '0.0000',
        variant_options: variantOptions,
        requires_shipping: item.type !== 'digital',
        shipped_quantity: 0,
    };
}
