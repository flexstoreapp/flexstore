import { RadioCard } from '@/components/storefront/radio-card';
import { useFormatMoney } from '@/hooks/use-format-money';
import { isZero } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';

import { useCheckout } from './checkout-context';

export function DeliverySection() {
    const { shippingOptions, loading, selectedShippingOptionId, selectShippingRate, errors } = useCheckout();
    const { formatMoney } = useFormatMoney();

    return (
        <section aria-labelledby="co-ship-method-h" className="rounded-md border border-line bg-surface p-6 lg:p-7">
            <h2 id="co-ship-method-h" className="m-0 text-xl font-semibold text-ink">
                {__('Delivery')}
            </h2>

            {loading && shippingOptions.length === 0 ? (
                <div className="mt-5 animate-pulse divide-y divide-line-strong overflow-hidden rounded-md border border-line-strong">
                    {[0, 1].map((index) => (
                        <div key={index} className="h-16 bg-surface-2" />
                    ))}
                </div>
            ) : shippingOptions.length === 0 ? (
                <p className="mt-5 mb-0 text-sm text-muted">
                    {__('Enter your shipping address to see delivery options.')}
                </p>
            ) : (
                <div role="radiogroup" aria-labelledby="co-ship-method-h" className="mt-5 flex flex-col">
                    {shippingOptions.map((option) => {
                        const id = String(option.id);
                        const deliveryTime = option.delivery_time ? getTranslation(option.delivery_time) : null;
                        const carrier = option.provider ?? getTranslation(option.carrier_name);
                        const subtitle = [carrier, deliveryTime].filter(Boolean).join(' · ');

                        return (
                            <RadioCard
                                key={id}
                                checked={selectedShippingOptionId === id}
                                onSelect={() => selectShippingRate(id)}
                                className="rounded-none not-first:-mt-px first:rounded-t-md last:rounded-b-md"
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="block font-semibold text-ink">{getTranslation(option.name)}</span>
                                    {subtitle && <span className="mt-0.5 block text-sm text-muted">{subtitle}</span>}
                                </span>
                                <span className="text-md font-bold text-ink">
                                    {isZero(option.rate) ? __('Free') : formatMoney(option.rate)}
                                </span>
                            </RadioCard>
                        );
                    })}
                </div>
            )}

            {errors.shipping_rate_id && (
                <p role="alert" className="mt-3 mb-0 text-sm text-error">
                    {errors.shipping_rate_id}
                </p>
            )}
        </section>
    );
}
