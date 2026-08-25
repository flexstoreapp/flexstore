import { useState } from 'react';

import { InfoPopover } from '@/components/admin/info-popover';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { Product } from '@/types';

interface ProductInventoryProps {
    product?: Product;
    errors: Record<string, string>;
}

export function ProductInventory({ product, errors }: ProductInventoryProps) {
    const [trackStock, setTrackStock] = useState(product?.track_stock ?? false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Inventory')}</CardTitle>
                <CardDescription>{__('Manage your product inventory')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <Field>
                    <FieldLabel htmlFor="sku">{__('SKU')}</FieldLabel>
                    <Input id="sku" name="sku" type="text" defaultValue={product?.sku ?? ''} />
                    <FieldError>{errors.sku}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="barcode">{__('Barcode (ISBN, UPC, GTIN, etc.)')}</FieldLabel>
                    <Input id="barcode" name="barcode" type="text" defaultValue={product?.barcode ?? ''} />
                    <FieldError>{errors.barcode}</FieldError>
                </Field>

                <Field orientation="horizontal">
                    <Checkbox
                        id="track-stock"
                        name="track_stock"
                        defaultChecked={trackStock}
                        onCheckedChange={(checked: boolean) => setTrackStock(checked)}
                    />
                    <FieldLabel htmlFor="track-stock">{__('Track stock')}</FieldLabel>
                </Field>

                {trackStock && (
                    <>
                        <Field>
                            <FieldLabel htmlFor="stock">{__('Stock')}</FieldLabel>
                            <Input
                                id="stock"
                                name="stock"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                placeholder="0"
                                defaultValue={String(product?.stock ?? '')}
                            />
                            <FieldError>{errors.stock}</FieldError>
                        </Field>

                        <Field>
                            <div className="flex items-center gap-2">
                                <FieldLabel htmlFor="low-stock-threshold">{__('Low stock threshold')}</FieldLabel>
                                <InfoPopover
                                    className="w-70"
                                    text={__(
                                        'Alert when stock falls at or below this number. Leave empty to use default threshold.',
                                    )}
                                />
                            </div>
                            <Input
                                id="low-stock-threshold"
                                name="low_stock_threshold"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                defaultValue={String(product?.low_stock_threshold ?? '')}
                            />
                            <FieldError>{errors.low_stock_threshold}</FieldError>
                        </Field>
                    </>
                )}

                <Field orientation="horizontal">
                    <Checkbox id="in-stock" name="in_stock" defaultChecked={product?.in_stock ?? true} />
                    <FieldLabel htmlFor="in-stock">{__('In stock')}</FieldLabel>
                </Field>
            </CardContent>
        </Card>
    );
}
