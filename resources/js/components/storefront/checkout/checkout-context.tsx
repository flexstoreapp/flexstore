import { router, useForm, usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useRef, type FormEvent, type ReactNode } from 'react';

import * as CheckoutController from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { useConfirm } from '@/components/storefront/confirm';
import { useCheckoutAddressBook, type AddressSelection } from '@/hooks/storefront/use-checkout-address-book';
import { useCheckoutDraftAutosave } from '@/hooks/storefront/use-checkout-draft-autosave';
import { useCheckoutOptions } from '@/hooks/storefront/use-checkout-options';
import { usePaymentGateway } from '@/hooks/storefront/use-payment-gateway';
import { usePaymentOptions } from '@/hooks/storefront/use-payment-options';
import { isAddressReadyForRates, useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';
import { analytics } from '@/lib/analytics';
import {
    buildCartSignature,
    buildCheckoutSubmitData,
    extractGeneralErrors,
    resolveInitialAddresses,
} from '@/lib/checkout-utils';
import { add, isPositive, subtract } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type {
    AddressFieldRules,
    AddressFormValues,
    AddressPrefix,
    CheckoutFormValues,
    CustomerAddress,
    DisplayTaxTotals,
    PaymentGatewayAdapter,
    PaymentOption,
    RecoveredCheckout,
    ShippingOption,
    StorefrontSharedData,
    TaxDetail,
} from '@/types';

export interface CheckoutPageProps {
    savedAddresses: CustomerAddress[];
    storeCountryCode: string;
    pricesIncludeTax: boolean;
    displayTaxTotals: DisplayTaxTotals;
    recoveredCheckout: RecoveredCheckout | null;
    addressFieldRules: AddressFieldRules;
}

interface OrderSummary {
    subtotal: string;
    shipping_total: string;
    tax_total: string;
    discount_total: string;
    total: string;
}

interface CouponControls {
    couponCode: string;
    appliedCoupon: string | null;
    onCouponCodeChange: (code: string) => void;
}

interface CheckoutContextValue {
    savedAddresses: CustomerAddress[];
    storeCountryCode: string;
    pricesIncludeTax: boolean;
    displayTaxTotals: DisplayTaxTotals;
    addressFieldRules: AddressFieldRules;

    items: NonNullable<StorefrontSharedData['cart']['items']>;
    requiresShipping: boolean;
    isFree: boolean;
    isAuthenticated: boolean;
    userEmail: string | null;
    name: string | null;

    email: string;
    setEmail: (value: string) => void;

    shippingAddress: AddressFormValues;
    billingAddress: AddressFormValues;
    setAddressField: (prefix: AddressPrefix, key: keyof AddressFormValues, value: string) => void;
    commitAddress: (prefix: AddressPrefix, address: AddressFormValues) => void;
    selectedAddressId: AddressSelection;
    selectSavedAddress: (address: CustomerAddress) => void;
    editSavedAddress: (address: CustomerAddress) => void;
    useNewAddress: () => void;
    saveShippingAddress: boolean;
    setSaveShippingAddress: (value: boolean) => void;

    differentBillingAddress: boolean;
    setDifferentBillingAddress: (value: boolean) => void;

    notes: string;
    setNotes: (value: string) => void;

    shippingOptions: ShippingOption[];
    paymentOptions: PaymentOption[];
    loading: boolean;
    paymentLoading: boolean;
    selectedShippingOptionId: string;
    selectShippingRate: (id: string) => void;
    paymentGatewayId: string;
    selectPaymentGateway: (id: string) => void;
    gateway: PaymentGatewayAdapter;

    errors: Record<string, string>;
    generalErrors: string[];

    summary: OrderSummary;
    shippingRateName: string | undefined;
    taxDetails: TaxDetail[] | undefined;
    coupon: CouponControls;
    canSubmit: boolean;
    flushDraft: () => void;
    submitCheckout: (event: FormEvent) => Promise<void>;
}

const CheckoutContext = createContext<CheckoutContextValue | null>(null);

export function useCheckout(): CheckoutContextValue {
    const context = useContext(CheckoutContext);
    if (!context) {
        throw new Error('useCheckout must be used within a CheckoutProvider');
    }
    return context;
}

export function CheckoutProvider({ children, ...props }: CheckoutPageProps & { children: ReactNode }) {
    const {
        savedAddresses,
        storeCountryCode,
        pricesIncludeTax,
        displayTaxTotals,
        recoveredCheckout,
        addressFieldRules,
    } = props;

    const { cart, auth, activeCurrency } = usePage<StorefrontSharedData>().props;
    const items = cart.items ?? [];
    const hasItems = items.length > 0;
    const requiresShipping = cart.requires_shipping;

    const { defaultAddress, initialShipping, initialBilling } = resolveInitialAddresses(
        savedAddresses,
        recoveredCheckout,
        requiresShipping,
        storeCountryCode,
    );

    const form = useForm<CheckoutFormValues>({
        customer_email: recoveredCheckout?.customer_email ?? auth.user?.email ?? '',
        shipping_address: initialShipping,
        different_billing_address: recoveredCheckout?.different_billing_address ?? false,
        billing_address: initialBilling,
        save_shipping_address: false,
        notes: recoveredCheckout?.notes ?? '',
        coupon_code: cart.coupon_code ?? '',
        shipping_rate_id: cart.shipping_rate_id ? String(cart.shipping_rate_id) : '',
        shipping_quote_reference: cart.shipping_quote_reference ?? '',
        payment_gateway_id: cart.payment_gateway_id ? String(cart.payment_gateway_id) : '',
    });

    const addressBook = useCheckoutAddressBook({
        form,
        requiresShipping,
        storeCountryCode,
        savedAddresses,
        recoveredCheckout,
        defaultAddress,
        initialShipping,
        initialBilling,
    });
    const { committedShipping, committedBilling, regionAddress, selectedAddressId } = addressBook;

    const beginCheckoutTracked = useRef(false);
    useEffect(() => {
        if (beginCheckoutTracked.current || !hasItems) return;
        beginCheckoutTracked.current = true;
        analytics.beginCheckout(cart, activeCurrency);
    }, [cart, activeCurrency, hasItems]);

    const formErrors = form.errors as Record<string, string>;

    const scrollToFirstError = useCallback(() => {
        requestAnimationFrame(() => {
            document
                .querySelector('[aria-invalid="true"], [role="alert"]')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }, []);

    const selectedShippingOptionId = form.data.shipping_quote_reference
        ? `${form.data.shipping_rate_id}:${form.data.shipping_quote_reference}`
        : form.data.shipping_rate_id;

    const { fieldRules: shippingRules, loading: shippingRulesLoading } = useAddressFieldRules(
        form.data.shipping_address.country_code,
        addressFieldRules,
    );
    const { fieldRules: billingRules, loading: billingRulesLoading } = useAddressFieldRules(
        form.data.billing_address.country_code,
        requiresShipping ? undefined : addressFieldRules,
    );

    const addressReady = requiresShipping
        ? !shippingRulesLoading &&
          isAddressReadyForRates(committedShipping, shippingRules) &&
          (!form.data.different_billing_address ||
              (!billingRulesLoading && isAddressReadyForRates(committedBilling, billingRules)))
        : !billingRulesLoading && isAddressReadyForRates(committedBilling, billingRules);

    const draft = useCheckoutDraftAutosave({
        enabled: hasItems,
        requiresShipping,
        email: form.data.customer_email,
        shippingAddress: form.data.shipping_address,
        differentBillingAddress: form.data.different_billing_address,
        billingAddress: form.data.billing_address,
        notes: form.data.notes,
        customerAddressId: typeof selectedAddressId === 'number' ? selectedAddressId : null,
        onCouponRemoved: () => {
            form.setData('coupon_code', '');
            router.reload({ only: ['cart'] });
        },
        onSaved: () => form.setDefaults(),
    });

    const cartSignature = buildCartSignature(cart);

    const {
        shippingOptions,
        taxEstimate,
        taxDetails,
        loading,
        selectShippingRate,
        setSubmitting: setShippingSubmitting,
    } = useCheckoutOptions({
        hasItems,
        requiresShipping,
        addressReady,
        shippingAddress: committedShipping,
        differentBillingAddress: form.data.different_billing_address,
        billingAddress: committedBilling,
        cartSignature,
        currentShippingRateId: selectedShippingOptionId,
        setShippingSelection: (rateId, quoteReference) =>
            form.setData((prev) => ({
                ...prev,
                shipping_rate_id: rateId,
                shipping_quote_reference: quoteReference ?? '',
            })),
    });

    const {
        paymentOptions,
        selectedPaymentOption,
        loading: paymentLoading,
        selectPaymentGateway,
        setSubmitting: setPaymentSubmitting,
    } = usePaymentOptions({
        hasItems,
        address: {
            country_code: regionAddress.country_code,
            state: regionAddress.state,
            postal_code: regionAddress.postal_code,
        },
        cartSignature,
        currentPaymentGatewayId: form.data.payment_gateway_id,
        setPaymentGatewayId: (id) => form.setData('payment_gateway_id', id),
    });

    const setSubmitting = (value: boolean) => {
        setShippingSubmitting(value);
        setPaymentSubmitting(value);
    };

    const taxTotal = isPositive(taxEstimate) ? taxEstimate : cart.tax_total;
    const total = add(subtract(cart.subtotal, cart.discount_total), add(cart.shipping_total, taxTotal));
    const summary: OrderSummary = {
        subtotal: cart.subtotal,
        shipping_total: cart.shipping_total,
        tax_total: taxTotal,
        discount_total: cart.discount_total,
        total,
    };

    const isFree = !isPositive(total);
    const hasShippingSelected = !requiresShipping || !!form.data.shipping_rate_id;
    const hasPaymentSelected = isFree || !!form.data.payment_gateway_id;
    const canSubmit = hasItems && hasShippingSelected && hasPaymentSelected && !(paymentLoading && !isFree);

    const selectedShippingOption = shippingOptions.find((option) => String(option.id) === selectedShippingOptionId);
    const shippingRateName = selectedShippingOption ? getTranslation(selectedShippingOption.name) : undefined;

    const gateway = usePaymentGateway({
        selectedOption: isFree ? null : selectedPaymentOption,
        amount: total,
        currencyCode: activeCurrency,
        submitUrl: CheckoutController.store().url,
        pageComponent: 'storefront/checkout/create',
        formData: buildCheckoutSubmitData(form.data, requiresShipping),
        setFormError: (key, message) => form.setError(key as keyof typeof form.data, message),
        scrollToFirstError,
        setSubmitting,
    });

    const { confirm } = useConfirm();

    useUnsavedChangesAlert(form.isDirty, {
        enabled: !gateway.processing,
        description: __('Your checkout is not complete. Are you sure you want to leave?'),
        confirm,
    });

    const selectShippingRateTracked = (id: string) => {
        selectShippingRate(id);
        const option = shippingOptions.find((o) => String(o.id) === id);
        analytics.addShippingInfo(cart, activeCurrency, option ? getTranslation(option.name) : undefined);
    };

    const selectPaymentGatewayTracked = (id: string) => {
        selectPaymentGateway(id);
        const option = paymentOptions.find((o) => String(o.id) === id);
        analytics.addPaymentInfo(cart, activeCurrency, option ? getTranslation(option.name) : undefined);
    };

    const submitCheckout = async (event: FormEvent) => {
        event.preventDefault();
        if (gateway.replacesSubmitButton) return;
        draft.cancel();
        await gateway.handleSubmit();
    };

    const value: CheckoutContextValue = {
        savedAddresses,
        storeCountryCode,
        pricesIncludeTax,
        displayTaxTotals,
        addressFieldRules,
        items,
        requiresShipping,
        isFree,
        isAuthenticated: auth.user !== null,
        userEmail: auth.user?.email ?? null,
        name: auth.user?.name ?? null,

        email: form.data.customer_email,
        setEmail: (value) => {
            form.setData('customer_email', value);
            form.clearErrors('customer_email');
        },

        shippingAddress: form.data.shipping_address,
        billingAddress: form.data.billing_address,
        setAddressField: addressBook.setAddressField,
        commitAddress: addressBook.commitAddress,
        selectedAddressId,
        selectSavedAddress: addressBook.selectSavedAddress,
        editSavedAddress: addressBook.editSavedAddress,
        useNewAddress: addressBook.useNewAddress,
        saveShippingAddress: form.data.save_shipping_address,
        setSaveShippingAddress: (value) => form.setData('save_shipping_address', value),

        differentBillingAddress: form.data.different_billing_address,
        setDifferentBillingAddress: (value) => form.setData('different_billing_address', value),

        notes: form.data.notes,
        setNotes: (value) => {
            form.setData('notes', value);
            form.clearErrors('notes');
        },

        shippingOptions,
        paymentOptions,
        loading,
        paymentLoading,
        selectedShippingOptionId,
        selectShippingRate: selectShippingRateTracked,
        paymentGatewayId: form.data.payment_gateway_id,
        selectPaymentGateway: selectPaymentGatewayTracked,
        gateway,

        errors: formErrors,
        generalErrors: extractGeneralErrors(formErrors),

        summary,
        shippingRateName,
        taxDetails,
        coupon: {
            couponCode: form.data.coupon_code,
            appliedCoupon: cart.coupon_code ?? null,
            onCouponCodeChange: (code) => form.setData('coupon_code', code),
        },
        canSubmit,
        flushDraft: draft.flush,
        submitCheckout,
    };

    return <CheckoutContext.Provider value={value}>{children}</CheckoutContext.Provider>;
}
