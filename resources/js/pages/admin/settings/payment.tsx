import { router } from '@inertiajs/react';

import * as PaymentGatewayController from '@/actions/App/Http/Controllers/Admin/PaymentGatewayController';
import * as PaymentSettingController from '@/actions/App/Http/Controllers/Admin/PaymentSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { Heading } from '@/components/admin/heading';
import { Cod } from '@/components/admin/payment/cod';
import { MercadoPago } from '@/components/admin/payment/mercadopago';
import { Mollie } from '@/components/admin/payment/mollie';
import { Paypal } from '@/components/admin/payment/paypal';
import { Paystack } from '@/components/admin/payment/paystack';
import { Razorpay } from '@/components/admin/payment/razorpay';
import { Stripe } from '@/components/admin/payment/stripe';
import { Tap } from '@/components/admin/payment/tap';
import { SectionHeading } from '@/components/admin/section-heading';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, PaymentGatewayDriver } from '@/types';

export default function Payment({ paymentGateways }: { paymentGateways: PaymentGateway[] }) {
    const gatewaysByDriver = paymentGateways.reduce(
        (acc, paymentGateway) => {
            acc[paymentGateway.driver] = paymentGateway;
            return acc;
        },
        {} as Partial<Record<PaymentGatewayDriver, PaymentGateway>>,
    );

    const handleToggle = (paymentGateway: PaymentGateway | undefined, checked: boolean) => {
        if (paymentGateway) {
            router.patch(
                PaymentGatewayController.update(paymentGateway),
                { is_active: checked },
                {
                    preserveScroll: true,
                    only: ['paymentGateways'],
                },
            );
        }
    };

    return (
        <>
            <Heading
                title={__('Payment')}
                description={__('Integration with payment gateways')}
                backHref={SettingController.index()}
            />

            <div className="space-y-6">
                <section className="space-y-4">
                    <SectionHeading>{__('Integrated gateways')}</SectionHeading>

                    <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                        <Stripe paymentGateway={gatewaysByDriver.stripe} onToggle={handleToggle} />
                        <Paypal paymentGateway={gatewaysByDriver.paypal} onToggle={handleToggle} />
                        <Razorpay paymentGateway={gatewaysByDriver.razorpay} onToggle={handleToggle} />
                        <Mollie paymentGateway={gatewaysByDriver.mollie} onToggle={handleToggle} />
                        <Tap paymentGateway={gatewaysByDriver.tap} onToggle={handleToggle} />
                        <Paystack paymentGateway={gatewaysByDriver.paystack} onToggle={handleToggle} />
                        <MercadoPago paymentGateway={gatewaysByDriver.mercadopago} onToggle={handleToggle} />
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading>{__('Manual gateways')}</SectionHeading>

                    <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                        <Cod paymentGateway={gatewaysByDriver.cod} onToggle={handleToggle} />
                    </div>
                </section>
            </div>
        </>
    );
}

Payment.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Payment'), href: PaymentSettingController.show() },
    ],
};
