import { Suspense } from 'react';

import { CheckboxField } from '@/components/storefront/checkbox-field';
import { PaymentMethodList } from '@/components/storefront/payment-method-list';
import { __ } from '@/lib/i18n';

import { AddressFields } from './address-fields';
import { useCheckout } from './checkout-context';

export function PaymentSection() {
    const {
        paymentOptions,
        paymentLoading,
        paymentGatewayId,
        selectPaymentGateway,
        gateway,
        requiresShipping,
        differentBillingAddress,
        setDifferentBillingAddress,
        billingAddress,
        errors,
        setAddressField,
        commitAddress,
    } = useCheckout();

    return (
        <section aria-labelledby="co-pay-h" className="rounded-md border border-line bg-surface p-6 lg:p-7">
            <h2 id="co-pay-h" className="m-0 text-xl font-semibold text-ink">
                {__('Payment')}
            </h2>

            <PaymentMethodList
                options={paymentOptions}
                loading={paymentLoading}
                selectedId={paymentGatewayId}
                onSelect={selectPaymentGateway}
                renderInlineContent={gateway.renderInlineContent}
                labelledBy="co-pay-h"
            />

            {errors.payment_gateway_id && (
                <p role="alert" className="mt-3 mb-0 text-sm text-error">
                    {errors.payment_gateway_id}
                </p>
            )}

            <Suspense fallback={null}>{gateway.renderHiddenComponent?.()}</Suspense>

            {requiresShipping && (
                <>
                    <div className="mt-5">
                        <CheckboxField
                            id="co-billing-diff"
                            label={__('My billing address is different from shipping')}
                            checked={differentBillingAddress}
                            onChange={setDifferentBillingAddress}
                        />
                    </div>

                    {differentBillingAddress && (
                        <div className="mt-5 border-t border-line pt-4">
                            <h3 className="m-0 mb-4 text-md font-semibold text-ink">{__('Billing address')}</h3>
                            <AddressFields
                                prefix="billing_address"
                                data={billingAddress}
                                errors={errors}
                                onChange={(key, value) => setAddressField('billing_address', key, value)}
                                onCommit={(address) => commitAddress('billing_address', address)}
                            />
                        </div>
                    )}
                </>
            )}
        </section>
    );
}
