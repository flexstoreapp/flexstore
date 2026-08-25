import { useState } from 'react';

import CouponValidationController from '@/actions/App/Http/Controllers/Admin/CouponValidationController';
import { AppliedCoupon, AppliedCouponRemoveButton } from '@/components/admin/applied-coupon';
import { SubmitButton } from '@/components/admin/submit-button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { httpPost, isHttpError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { Coupon, Order } from '@/types';

interface CouponValidationResponse {
    coupon: Coupon;
    discount: string;
    message: string;
}

interface OrderFormCouponProps {
    order?: Order;
    customerEmail: string;
    subtotal: string;
    onDiscountChange: (discount: string) => void;
}

export function OrderFormCoupon({ order, customerEmail, subtotal, onDiscountChange }: OrderFormCouponProps) {
    const [couponCode, setCouponCode] = useState(order?.coupon_code ?? '');
    const [isValid, setIsValid] = useState<boolean | null>(!!order?.coupon_code);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    const reset = () => {
        setIsValid(null);
        setError('');
        onDiscountChange('0.0000');
    };

    const handleCouponBlur = () => {
        if (!couponCode.trim()) {
            reset();
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleApplyCoupon();
        }
    };

    const handleRemoveCoupon = () => {
        setCouponCode('');
        reset();
    };

    const handleApplyCoupon = async () => {
        setError('');
        setIsLoading(true);

        try {
            const data = await httpPost<CouponValidationResponse>(CouponValidationController(), {
                coupon_code: couponCode,
                subtotal: subtotal,
                customer_email: customerEmail,
            });

            setIsValid(true);
            onDiscountChange(data.discount);
            setIsLoading(false);
        } catch (error) {
            if (isHttpError(error) && error.data?.message) {
                setError(error.data.message as string);
            } else {
                setError(__('Failed to apply coupon.'));
            }

            setIsValid(false);
            onDiscountChange('0.0000');
            setIsLoading(false);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Coupon')}</CardTitle>
                <CardDescription>{__('Apply a discount coupon to the order')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {isValid && couponCode ? (
                    <AppliedCoupon code={couponCode}>
                        <AppliedCouponRemoveButton
                            code={couponCode}
                            onClick={handleRemoveCoupon}
                            disabled={isLoading}
                        />
                    </AppliedCoupon>
                ) : (
                    <Field>
                        <div className="flex gap-2">
                            <Input
                                type="text"
                                name="coupon_code"
                                value={couponCode}
                                onChange={(e) => setCouponCode(e.target.value)}
                                onBlur={handleCouponBlur}
                                onKeyDown={handleKeyPress}
                                placeholder={__('Enter coupon code')}
                                aria-label={__('Coupon code')}
                                disabled={isLoading}
                            />
                            <SubmitButton
                                type="button"
                                variant="outline"
                                size="md"
                                processing={isLoading}
                                onClick={handleApplyCoupon}
                                disabled={isLoading || !couponCode.trim()}
                            >
                                {__('Apply')}
                            </SubmitButton>
                        </div>
                        <FieldError>{error}</FieldError>
                    </Field>
                )}
            </CardContent>
        </Card>
    );
}
