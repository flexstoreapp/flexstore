import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import type { Coupon, CouponType } from '@/types';

interface CouponFormBasicInfoProps {
    coupon?: Coupon;
    couponType: CouponType;
    onChangeCouponType: (type: CouponType) => void;
    errors: Record<string, string>;
}

export function CouponFormBasicInfo({ coupon, couponType, onChangeCouponType, errors }: CouponFormBasicInfoProps) {
    const { currencyFormat } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps();

    return (
        <Card>
            <CardContent className="space-y-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel htmlFor="coupon-code">{__('Coupon code')}</FieldLabel>
                        <Input
                            id="coupon-code"
                            name="code"
                            defaultValue={coupon?.code ?? ''}
                            className="uppercase"
                            required
                        />
                        <FieldError>{errors.code}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="discount-value">{__('Discount value')}</FieldLabel>
                        <InputGroup.Root>
                            <InputGroup.Prefix>
                                {couponType === 'percentage' ? '%' : currencyFormat.symbol}
                            </InputGroup.Prefix>
                            <InputGroup.Control
                                key={couponType}
                                {...(couponType === 'percentage'
                                    ? {
                                          type: 'number',
                                          inputMode: 'decimal',
                                          min: '0',
                                          step: '0.01',
                                          placeholder: '0.00',
                                      }
                                    : moneyInputProps)}
                                id="discount-value"
                                name="value"
                                max={couponType === 'percentage' ? 100 : undefined}
                                defaultValue={coupon?.value ?? ''}
                            />
                            <InputGroup.Select
                                name="type"
                                defaultValue={coupon?.type ?? 'flat'}
                                options={[
                                    { value: 'flat', label: __('Flat amount') },
                                    { value: 'percentage', label: __('Percentage') },
                                ]}
                                onValueChange={(value) => onChangeCouponType(value as CouponType)}
                            />
                        </InputGroup.Root>
                        <FieldError>{errors.value}</FieldError>
                        <FieldError>{errors.type}</FieldError>
                    </Field>
                </div>
            </CardContent>
        </Card>
    );
}
