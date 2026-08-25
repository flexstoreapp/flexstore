import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { __ } from '@/lib/i18n';
import { stringifyVariantName } from '@/lib/product-form-utils';
import { type ProductVariant } from '@/types';

interface VariantInventoryProps {
    variants: ProductVariant[];
    errors: Record<string, string>;
    onVariantUpdate: (variantId: string, updates: Partial<ProductVariant>) => void;
    onAllVariantsUpdate: (updates: Partial<ProductVariant>) => void;
}

export function VariantInventory({ variants, errors, onVariantUpdate, onAllVariantsUpdate }: VariantInventoryProps) {
    return (
        <div className="space-y-6">
            <div className="flex flex-wrap justify-end gap-4">
                <Field orientation="horizontal" className="w-auto">
                    <Checkbox
                        id="all-track-stock"
                        onCheckedChange={(checked: boolean) => onAllVariantsUpdate({ track_stock: checked })}
                    />
                    <FieldLabel htmlFor="all-track-stock" className="text-xs">
                        {__('Track stock')}
                    </FieldLabel>
                </Field>

                <Field className="w-30 gap-2">
                    <FieldLabel htmlFor="all-stock" className="text-xs">
                        {__('Stock')}
                    </FieldLabel>
                    <Input
                        id="all-stock"
                        type="number"
                        inputMode="numeric"
                        min="0"
                        step="1"
                        placeholder="0"
                        className="h-8"
                        onChange={(e) => onAllVariantsUpdate({ stock: e.target.value as unknown as number })}
                    />
                </Field>

                <Field className="w-30 gap-2">
                    <FieldLabel htmlFor="all-low-stock-threshold" className="text-xs">
                        {__('Low stock threshold')}
                    </FieldLabel>
                    <Input
                        id="all-low-stock-threshold"
                        type="number"
                        inputMode="numeric"
                        min="0"
                        step="1"
                        placeholder={__('Default')}
                        className="h-8"
                        onChange={(e) =>
                            onAllVariantsUpdate({
                                low_stock_threshold: e.target.value ? (e.target.value as unknown as number) : null,
                            })
                        }
                    />
                </Field>

                <Field orientation="horizontal" className="w-auto">
                    <Checkbox
                        id="all-in-stock"
                        onCheckedChange={(checked: boolean) => onAllVariantsUpdate({ in_stock: checked })}
                    />
                    <FieldLabel htmlFor="all-in-stock" className="text-xs">
                        {__('In stock')}
                    </FieldLabel>
                </Field>
            </div>

            <ScrollArea className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="whitespace-nowrap">{__('Variant')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('SKU')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Barcode')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Track stock')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Stock')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Low stock threshold')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('In stock')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {variants.map((variant, index) => (
                            <TableRow key={variant.id}>
                                <TableCell className="font-medium">{stringifyVariantName(variant)}</TableCell>
                                <TableCell className="space-y-1.5">
                                    <Input
                                        name={`variants.${index}.sku`}
                                        value={variant.sku ?? ''}
                                        onChange={(e) => onVariantUpdate(variant.id, { sku: e.target.value })}
                                        className="h-8 w-30"
                                    />
                                    <FieldError className="text-xs">{errors[`variants.${index}.sku`]}</FieldError>
                                </TableCell>
                                <TableCell className="space-y-1.5">
                                    <Input
                                        name={`variants.${index}.barcode`}
                                        value={variant.barcode ?? ''}
                                        onChange={(e) => onVariantUpdate(variant.id, { barcode: e.target.value })}
                                        className="h-8 w-30"
                                    />
                                    <FieldError className="text-xs">{errors[`variants.${index}.barcode`]}</FieldError>
                                </TableCell>
                                <TableCell>
                                    <input
                                        type="hidden"
                                        name={`variants.${index}.track_stock`}
                                        value={variant.track_stock ? '1' : '0'}
                                    />
                                    <Checkbox
                                        id={`track-stock-${variant.id}`}
                                        checked={variant.track_stock}
                                        onCheckedChange={(checked: boolean) =>
                                            onVariantUpdate(variant.id, { track_stock: checked })
                                        }
                                    />
                                </TableCell>
                                <TableCell className="space-y-1.5">
                                    <Input
                                        name={`variants.${index}.stock`}
                                        type="number"
                                        inputMode="numeric"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        value={variant.stock ?? ''}
                                        disabled={!variant.track_stock}
                                        onChange={(e) =>
                                            onVariantUpdate(variant.id, {
                                                stock: e.target.value as unknown as number,
                                            })
                                        }
                                        className="h-8 w-30"
                                    />
                                    <FieldError className="text-xs">{errors[`variants.${index}.stock`]}</FieldError>
                                </TableCell>
                                <TableCell className="space-y-1.5">
                                    <Input
                                        name={`variants.${index}.low_stock_threshold`}
                                        type="number"
                                        inputMode="numeric"
                                        min="0"
                                        step="1"
                                        placeholder={__('Default')}
                                        value={variant.low_stock_threshold ?? ''}
                                        disabled={!variant.track_stock}
                                        onChange={(e) =>
                                            onVariantUpdate(variant.id, {
                                                low_stock_threshold: e.target.value
                                                    ? (e.target.value as unknown as number)
                                                    : null,
                                            })
                                        }
                                        className="h-8 w-30"
                                    />
                                    <FieldError className="text-xs">
                                        {errors[`variants.${index}.low_stock_threshold`]}
                                    </FieldError>
                                </TableCell>
                                <TableCell>
                                    <input
                                        type="hidden"
                                        name={`variants.${index}.in_stock`}
                                        value={variant.in_stock ? '1' : '0'}
                                    />
                                    <Checkbox
                                        id={`in-stock-${variant.id}`}
                                        checked={variant.in_stock}
                                        onCheckedChange={(checked: boolean) =>
                                            onVariantUpdate(variant.id, { in_stock: checked })
                                        }
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>
    );
}
