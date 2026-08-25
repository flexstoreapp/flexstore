import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import type { Coupon } from '@/types';

interface CouponFormAvailabilityProps {
    coupon?: Coupon;
    errors: Record<string, string>;
}

export function CouponFormAvailability({ coupon, errors }: CouponFormAvailabilityProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Availability')}</CardTitle>
                <CardDescription>{__('Control coupon availability and validity period')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <Field>
                    <FieldLabel htmlFor="starts-at-date">{__('Start date & time')}</FieldLabel>
                    <DateTimePicker id="starts-at" name="starts_at" defaultValue={coupon?.starts_at} />
                    <FieldError>{errors.starts_at}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="expires-at-date">{__('Expiry date & time')}</FieldLabel>
                    <DateTimePicker id="expires-at" name="expires_at" defaultValue={coupon?.expires_at} />
                    <FieldError>{errors.expires_at}</FieldError>
                </Field>

                <Field orientation="horizontal">
                    <Checkbox id="is-active" name="is_active" defaultChecked={coupon?.is_active ?? true} />
                    <FieldLabel htmlFor="is-active">{__('Active')}</FieldLabel>
                </Field>
            </CardContent>
        </Card>
    );
}
