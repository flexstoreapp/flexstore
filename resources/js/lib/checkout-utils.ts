import type {
    AddressFormValues,
    Cart,
    CheckoutFormValues,
    CustomerAddress,
    RecoveredAddress,
    RecoveredCheckout,
} from '@/types';

export const emptyAddress: AddressFormValues = {
    first_name: '',
    last_name: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country_code: '',
    phone: '',
};

export function asAddressFormValues(source: RecoveredAddress | null | undefined): AddressFormValues {
    if (!source) return { ...emptyAddress };
    return {
        first_name: source.first_name ?? '',
        last_name: source.last_name ?? '',
        address_line_1: source.address_line_1 ?? '',
        address_line_2: source.address_line_2 ?? '',
        city: source.city ?? '',
        state: source.state ?? '',
        postal_code: source.postal_code ?? '',
        country_code: source.country_code ?? '',
        phone: source.phone ?? '',
    };
}

export function addressToFormValues(address: CustomerAddress): AddressFormValues {
    return {
        first_name: address.first_name,
        last_name: address.last_name,
        address_line_1: address.address_line_1,
        address_line_2: address.address_line_2 ?? '',
        city: address.city,
        state: address.state,
        postal_code: address.postal_code,
        country_code: address.country_code,
        phone: address.phone,
    };
}

export function resolveInitialAddresses(
    savedAddresses: CustomerAddress[],
    recoveredCheckout: RecoveredCheckout | null,
    requiresShipping: boolean,
    storeCountryCode: string,
): { defaultAddress: CustomerAddress | null; initialShipping: AddressFormValues; initialBilling: AddressFormValues } {
    const defaultAddress = savedAddresses.find((address) => address.is_default) ?? savedAddresses[0] ?? null;
    const emptyForStore = { ...emptyAddress, country_code: storeCountryCode };
    const fallbackAddress = defaultAddress ? asAddressFormValues(defaultAddress) : emptyForStore;

    const initialShipping = recoveredCheckout?.shipping_address
        ? asAddressFormValues(recoveredCheckout.shipping_address)
        : fallbackAddress;

    const initialBilling = recoveredCheckout?.billing_address
        ? asAddressFormValues(recoveredCheckout.billing_address)
        : requiresShipping
          ? emptyForStore
          : fallbackAddress;

    return { defaultAddress, initialShipping, initialBilling };
}

export function buildCartSignature(cart: Cart): string {
    const items = (cart.items ?? []).map((item) => `${item.product_id}x${item.quantity}`).join(',');
    return `${cart.subtotal}|${items}`;
}

export function buildCheckoutSubmitData(
    formData: CheckoutFormValues,
    requiresShipping: boolean,
): Record<string, unknown> {
    if (requiresShipping) {
        return formData as unknown as Record<string, unknown>;
    }

    return {
        customer_email: formData.customer_email,
        billing_address: formData.billing_address,
        different_billing_address: false,
        notes: formData.notes,
        coupon_code: formData.coupon_code,
        payment_gateway_id: formData.payment_gateway_id,
    };
}

export function extractGeneralErrors(errors: Record<string, string>): string[] {
    return Object.entries(errors)
        .filter(([key]) => key === 'cart' || key === 'shared_payment' || key.startsWith('items.'))
        .map(([, message]) => message);
}
