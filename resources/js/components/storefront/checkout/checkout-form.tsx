import { AddressBookSection } from '@/components/storefront/checkout/address-book-section';
import { useCheckout } from '@/components/storefront/checkout/checkout-context';
import { ContactSection } from '@/components/storefront/checkout/contact-section';
import { DeliverySection } from '@/components/storefront/checkout/delivery-section';
import { NotesSection } from '@/components/storefront/checkout/notes-section';
import { OrderSummary } from '@/components/storefront/checkout/order-summary';
import { PaymentSection } from '@/components/storefront/checkout/payment-section';
import { __ } from '@/lib/i18n';

export function CheckoutForm() {
    const { requiresShipping, isFree, submitCheckout, flushDraft } = useCheckout();

    return (
        <form onSubmit={submitCheckout} onBlur={() => flushDraft()}>
            <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_380px] lg:gap-8 xl:grid-cols-[1fr_420px]">
                <div className="flex flex-col gap-6">
                    <ContactSection />
                    {requiresShipping ? (
                        <>
                            <AddressBookSection
                                title={__('Shipping address')}
                                prefix="shipping_address"
                                showSaveOption
                            />
                            <DeliverySection />
                        </>
                    ) : (
                        <AddressBookSection
                            title={__('Billing address')}
                            prefix="billing_address"
                            showSaveOption={false}
                        />
                    )}
                    {!isFree && <PaymentSection />}
                    <NotesSection />
                </div>

                <OrderSummary />
            </div>
        </form>
    );
}
