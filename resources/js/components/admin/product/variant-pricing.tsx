import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { InputGroup } from '@/components/ui/input-group';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import { stringifyVariantName, calculateProfit, calculateProfitMargin } from '@/lib/product-form-utils';
import { type ProductVariant } from '@/types';

interface VariantPricingProps {
    variants: ProductVariant[];
    errors: Record<string, string>;
    onVariantUpdate: (variantId: string, updates: Partial<ProductVariant>) => void;
    onAllVariantsUpdate: (updates: Partial<ProductVariant>) => void;
}

export function VariantPricing({ variants, errors, onVariantUpdate, onAllVariantsUpdate }: VariantPricingProps) {
    const { formatMoney, currencyFormat } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps();

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap justify-end gap-4">
                <Field className="w-30 gap-2">
                    <FieldLabel htmlFor="all-price" className="text-xs">
                        {__('Price')}
                    </FieldLabel>
                    <InputGroup.Root className="h-8 w-30">
                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                        <InputGroup.Control
                            {...moneyInputProps}
                            id="all-price"
                            onChange={(e) => onAllVariantsUpdate({ price: e.target.value })}
                        />
                    </InputGroup.Root>
                </Field>
                <Field className="w-30 gap-2">
                    <FieldLabel htmlFor="all-compare-at-price" className="text-xs">
                        {__('Compare-at price')}
                    </FieldLabel>
                    <InputGroup.Root className="h-8 w-30">
                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                        <InputGroup.Control
                            {...moneyInputProps}
                            id="all-compare-at-price"
                            onChange={(e) => onAllVariantsUpdate({ compare_at_price: e.target.value })}
                        />
                    </InputGroup.Root>
                </Field>
                <Field className="w-30 gap-2">
                    <FieldLabel htmlFor="all-cost-per-item" className="text-xs">
                        {__('Cost per item')}
                    </FieldLabel>
                    <InputGroup.Root className="h-8 w-30">
                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                        <InputGroup.Control
                            {...moneyInputProps}
                            id="all-cost-per-item"
                            onChange={(e) => onAllVariantsUpdate({ cost_per_item: e.target.value })}
                        />
                    </InputGroup.Root>
                </Field>
            </div>

            <ScrollArea className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="whitespace-nowrap">{__('Variant')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Price')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Compare-at price')}</TableHead>
                            <TableHead className="whitespace-nowrap">{__('Cost per item')}</TableHead>
                            <TableHead className="text-end whitespace-nowrap">{__('Profit')}</TableHead>
                            <TableHead className="text-end whitespace-nowrap">{__('Margin')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {variants.map((variant, index) => (
                            <TableRow key={variant.id}>
                                <TableCell className="font-medium">{stringifyVariantName(variant)}</TableCell>
                                <TableCell className="space-y-1.5">
                                    <InputGroup.Root className="h-8 w-30">
                                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                                        <InputGroup.Control
                                            {...moneyInputProps}
                                            name={`variants.${index}.price`}
                                            value={variant.price ?? ''}
                                            onChange={(e) => onVariantUpdate(variant.id, { price: e.target.value })}
                                        />
                                    </InputGroup.Root>
                                    <FieldError className="text-xs">{errors[`variants.${index}.price`]}</FieldError>
                                </TableCell>
                                <TableCell className="space-y-1.5">
                                    <InputGroup.Root className="h-8 w-30">
                                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                                        <InputGroup.Control
                                            {...moneyInputProps}
                                            name={`variants.${index}.compare_at_price`}
                                            value={variant.compare_at_price ?? ''}
                                            onChange={(e) =>
                                                onVariantUpdate(variant.id, { compare_at_price: e.target.value })
                                            }
                                        />
                                    </InputGroup.Root>
                                    <FieldError className="text-xs">
                                        {errors[`variants.${index}.compare_at_price`]}
                                    </FieldError>
                                </TableCell>
                                <TableCell className="space-y-1.5">
                                    <InputGroup.Root className="h-8 w-30">
                                        <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                                        <InputGroup.Control
                                            {...moneyInputProps}
                                            name={`variants.${index}.cost_per_item`}
                                            value={variant.cost_per_item ?? ''}
                                            onChange={(e) =>
                                                onVariantUpdate(variant.id, { cost_per_item: e.target.value })
                                            }
                                        />
                                    </InputGroup.Root>
                                    <FieldError className="text-xs">
                                        {errors[`variants.${index}.cost_per_item`]}
                                    </FieldError>
                                </TableCell>
                                <TableCell className="text-end">
                                    <HelpBlock>
                                        {!variant.price || !variant.cost_per_item
                                            ? '—'
                                            : `${formatMoney(calculateProfit(variant.price, variant.cost_per_item))}`}
                                    </HelpBlock>
                                </TableCell>
                                <TableCell className="text-end">
                                    <HelpBlock>
                                        {!variant.price || !variant.cost_per_item
                                            ? '—'
                                            : `${calculateProfitMargin(variant.price, variant.cost_per_item)}%`}
                                    </HelpBlock>
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
