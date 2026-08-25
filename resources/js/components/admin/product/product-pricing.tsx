import { useState } from 'react';

import { InfoPopover } from '@/components/admin/info-popover';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import { calculateProfit, calculateProfitMargin } from '@/lib/product-form-utils';
import { type Product } from '@/types';

interface ProductPricingProps {
    product?: Product;
    errors: Record<string, string>;
}

export function ProductPricing({ product, errors }: ProductPricingProps) {
    const { formatMoney, currencyFormat } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps();

    const [price, setPrice] = useState(product?.price ?? '');
    const [costPerItem, setCostPerItem] = useState(product?.cost_per_item ?? '');

    const profit = !price || !costPerItem ? '—' : `${formatMoney(calculateProfit(price, costPerItem))}`;
    const margin = !price || !costPerItem ? '—' : `${calculateProfitMargin(price, costPerItem)}%`;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Pricing')}</CardTitle>
                <CardDescription>{__('Set your product pricing')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="price">{__('Price')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                            <InputGroup.Control
                                {...moneyInputProps}
                                id="price"
                                name="price"
                                defaultValue={String(product?.price ?? '')}
                                onChange={(e) => setPrice(e.target.value)}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.price}</FieldError>
                    </Field>
                    <Field>
                        <div className="flex items-center gap-2">
                            <FieldLabel htmlFor="compare-at-price">{__('Compare-at price')}</FieldLabel>
                            <InfoPopover
                                className="w-90"
                                text={__('Shown as a crossed-out next to the current price.')}
                            />
                        </div>
                        <InputGroup.Root>
                            <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                            <InputGroup.Control
                                {...moneyInputProps}
                                id="compare-at-price"
                                name="compare_at_price"
                                defaultValue={String(product?.compare_at_price ?? '')}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.compare_at_price}</FieldError>
                    </Field>
                </div>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Field>
                        <div className="flex items-center gap-2">
                            <FieldLabel htmlFor="cost-per-item">{__('Cost per item')}</FieldLabel>
                            <InfoPopover className="w-50" text={__('Customers won’t see this.')} />
                        </div>
                        <InputGroup.Root>
                            <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                            <InputGroup.Control
                                {...moneyInputProps}
                                id="cost-per-item"
                                name="cost_per_item"
                                defaultValue={String(product?.cost_per_item ?? '')}
                                onChange={(e) => setCostPerItem(e.target.value)}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.cost_per_item}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="profit">{__('Profit')}</FieldLabel>
                        <Input id="profit" value={profit} readOnly />
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="margin">{__('Margin')}</FieldLabel>
                        <Input id="margin" value={margin} readOnly />
                    </Field>
                </div>
            </CardContent>
        </Card>
    );
}
