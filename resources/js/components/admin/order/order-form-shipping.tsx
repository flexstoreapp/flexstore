import { InfoIcon } from 'lucide-react';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError } from '@/components/ui/field';
import { useOrderShippingOptions } from '@/hooks/admin/use-order-shipping-options';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { OrderAddress } from '@/types';

interface OrderFormShippingProps {
    items: Array<{ product_id: string; product_variant_id?: string; quantity: string }>;
    shippingAddress: OrderAddress | null;
    differentBillingAddress: boolean;
    billingAddress: OrderAddress | null;
    addressReady: boolean;
    addressKey: string;
    shippingRateId?: string;
    onShippingRateChange: (rateId: string, rate: string) => void;
    errors: Record<string, string>;
}

export function OrderFormShipping({
    items,
    shippingAddress,
    differentBillingAddress,
    billingAddress,
    addressReady,
    addressKey,
    shippingRateId,
    onShippingRateChange,
    errors,
}: OrderFormShippingProps) {
    const { formatMoney } = useFormatMoney();
    const { shippingOptions, loading, canFetchShipping, selectShippingRate } = useOrderShippingOptions({
        items,
        shippingAddress,
        differentBillingAddress,
        billingAddress,
        addressReady,
        addressKey,
        onShippingRateChange,
    });

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Shipping')}</CardTitle>
                <CardDescription>{__('Select a shipping method for the order')}</CardDescription>
            </CardHeader>

            <CardContent>
                {canFetchShipping ? (
                    <Field>
                        <AdaptiveSelect
                            name="shipping_rate_id"
                            value={shippingRateId}
                            onValueChange={selectShippingRate}
                            placeholder={loading ? __('Loading...') : __('Select a shipping method')}
                            aria-label={__('Shipping method')}
                            disabled={loading || shippingOptions.length === 0}
                            options={shippingOptions.map((option) => {
                                const carrier = option.provider ?? getTranslation(option.carrier_name);
                                const name = getTranslation(option.name);

                                return {
                                    value: String(option.id),
                                    label: `${carrier ? `${name} • ${carrier}` : name} — ${formatMoney(option.rate)}`,
                                };
                            })}
                        />
                        {shippingOptions.length === 0 && (
                            <FieldError>{__('No shipping methods available for this order.')}</FieldError>
                        )}
                        <FieldError>{errors.shipping_rate_id}</FieldError>
                    </Field>
                ) : (
                    <Alert>
                        <InfoIcon />
                        <AlertDescription>
                            {__('Add items and complete address to see shipping options.')}
                        </AlertDescription>
                    </Alert>
                )}
            </CardContent>
        </Card>
    );
}
