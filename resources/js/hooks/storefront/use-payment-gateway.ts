import { usePage } from '@inertiajs/react';

import type { GatewayContext, PaymentGatewayAdapter, PaymentOption, StorefrontSharedData } from '@/types';

import { useDefaultGateway } from './use-default-gateway';
import { useMercadoPagoGateway } from './use-mercadopago-gateway';
import { useMollieGateway } from './use-mollie-gateway';
import { useCurrencyDecimalPlaces } from '../use-money-input';
import { usePaypalGateway } from './use-paypal-gateway';
import { usePaystackGateway } from './use-paystack-gateway';
import { useRazorpayGateway } from './use-razorpay-gateway';
import { useStripeGateway } from './use-stripe-gateway';
import { useTapGateway } from './use-tap-gateway';

interface UsePaymentGatewayParams {
    selectedOption: PaymentOption | null;
    amount: string;
    currencyCode: string;
    submitUrl: string;
    pageComponent: string;
    formData: Record<string, unknown>;
    setFormError: (key: string, message: string) => void;
    scrollToFirstError: () => void;
    setSubmitting: (value: boolean) => void;
}

export function usePaymentGateway(params: UsePaymentGatewayParams): PaymentGatewayAdapter {
    const { storeName } = usePage<StorefrontSharedData>().props;
    const currencyDecimalPlaces = useCurrencyDecimalPlaces(params.currencyCode);

    const ctx: GatewayContext = {
        option: params.selectedOption,
        amount: params.amount,
        submitUrl: params.submitUrl,
        pageComponent: params.pageComponent,
        currencyCode: params.currencyCode,
        currencyDecimalPlaces,
        storeName,
        formData: params.formData,
        setFormError: params.setFormError,
        scrollToFirstError: params.scrollToFirstError,
        setSubmitting: params.setSubmitting,
    };

    const stripe = useStripeGateway(ctx);
    const paypal = usePaypalGateway(ctx);
    const razorpay = useRazorpayGateway(ctx);
    const mollie = useMollieGateway(ctx);
    const tap = useTapGateway(ctx);
    const paystack = usePaystackGateway(ctx);
    const mercadopago = useMercadoPagoGateway(ctx);
    const defaultGateway = useDefaultGateway(ctx);

    const driver = params.selectedOption?.driver;
    const isEmbedded = params.selectedOption?.checkout_mode === 'embedded';

    if (driver === 'stripe' && isEmbedded) return stripe;
    if (driver === 'paypal' && isEmbedded) return paypal;
    if (driver === 'razorpay' && isEmbedded) return razorpay;
    if (driver === 'mollie' && isEmbedded) return mollie;
    if (driver === 'tap' && isEmbedded) return tap;
    if (driver === 'paystack' && isEmbedded) return paystack;
    if (driver === 'mercadopago' && isEmbedded) return mercadopago;

    return defaultGateway;
}
