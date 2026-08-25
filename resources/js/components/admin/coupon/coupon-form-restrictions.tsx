import { useState } from 'react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Coupon, CouponType } from '@/types';

interface CouponFormRestrictionsProps {
    coupon?: Coupon;
    couponType: CouponType;
    errors: Record<string, string>;
}

export function CouponFormRestrictions({ coupon, couponType, errors }: CouponFormRestrictionsProps) {
    const moneyInputProps = useMoneyInputProps();
    const [maxDiscountInput, setMaxDiscountInput] = useState(coupon?.maximum_discount ?? '');
    const maxDiscount = couponType === 'flat' ? '' : maxDiscountInput;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Restrictions')}</CardTitle>
                <CardDescription>{__('Set limits and conditions for coupon usage')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="min-order-value">{__('Min order value')}</FieldLabel>
                        <Input
                            {...moneyInputProps}
                            id="min-order-value"
                            name="min_order_value"
                            defaultValue={coupon?.min_order_value ?? ''}
                        />
                        <FieldError>{errors.min_order_value}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="maximum-discount">{__('Max discount amount')}</FieldLabel>
                        <Input
                            {...moneyInputProps}
                            id="maximum-discount"
                            name="maximum_discount"
                            value={maxDiscount}
                            onChange={(e) => setMaxDiscountInput(e.target.value)}
                            className={cn(couponType === 'flat' && 'bg-muted')}
                            readOnly={couponType === 'flat'}
                        />
                        <FieldError>{errors.maximum_discount}</FieldError>
                    </Field>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="usage-limit">{__('Total usage limit')}</FieldLabel>
                        <Input
                            id="usage-limit"
                            name="usage_limit"
                            type="number"
                            min="1"
                            placeholder={__('No limit')}
                            defaultValue={coupon?.usage_limit ?? ''}
                        />
                        <FieldError>{errors.usage_limit}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="usage-limit-per-customer">{__('Usage limit per customer')}</FieldLabel>
                        <Input
                            id="usage-limit-per-customer"
                            name="usage_limit_per_customer"
                            type="number"
                            min="1"
                            placeholder={__('No limit')}
                            defaultValue={coupon?.usage_limit_per_customer ?? ''}
                        />
                        <FieldError>{errors.usage_limit_per_customer}</FieldError>
                    </Field>
                </div>
            </CardContent>
        </Card>
    );
}
