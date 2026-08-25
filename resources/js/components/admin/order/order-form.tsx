import { type FormDataConvertible } from '@inertiajs/core';
import { Form, useFormContext } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import OrderTaxCalculatorController from '@/actions/App/Http/Controllers/Admin/OrderTaxCalculatorController';
import { useConfirm } from '@/components/admin/confirm';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { isAddressReadyForRates, rateRelevantAddressKey, useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useDebounce } from '@/hooks/use-debounce';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';
import { add, isPositive, subtract, sum } from '@/lib/decimal';
import { httpPost, isAbortError, isHttpError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { DisplayTaxTotals, Order, OrderAddress, PaymentGateway, OrderItem } from '@/types';
import type { OrderTaxDetail, TaxDetail } from '@/types/tax';

import { OrderFormAddress } from './order-form-address';
import { OrderFormCoupon } from './order-form-coupon';
import { OrderFormCustomer } from './order-form-customer';
import { OrderFormItems, type OrderItemForm } from './order-form-items';
import { OrderFormNotes } from './order-form-notes';
import { OrderFormPayment } from './order-form-payment';
import { OrderFormShipping } from './order-form-shipping';
import { OrderFormSummary } from './order-form-summary';

function aggregateTaxDetails(details: OrderTaxDetail[]): TaxDetail[] {
    const grouped = new Map<string, TaxDetail>();

    for (const detail of details) {
        const key = `${detail.tax_rate_id ?? JSON.stringify(detail.tax_name)}:${detail.tax_rate}`;
        const existing = grouped.get(key);

        if (existing) {
            existing.taxable_amount = add(existing.taxable_amount, detail.taxable_amount);
            existing.tax_amount = add(existing.tax_amount, detail.tax_amount);
        } else {
            grouped.set(key, {
                tax_name: detail.tax_name,
                tax_rate: detail.tax_rate,
                taxable_amount: detail.taxable_amount,
                tax_amount: detail.tax_amount,
            });
        }
    }

    return [...grouped.values()];
}

function toFormItems(orderItem: OrderItem & { shipped_quantity?: number }): OrderItemForm {
    return {
        id: String(orderItem.id ?? ''),
        product_id: String(orderItem.product_id ?? ''),
        product_variant_id: String(orderItem.product_variant_id ?? ''),
        product_title: getTranslation(orderItem.product_title),
        variant_title: orderItem.variant_title,
        thumbnail_url: mediaSmallThumb(orderItem.media),
        media: orderItem.media,
        unit_price: orderItem.unit_price,
        quantity: String(orderItem.quantity ?? '1'),
        total_price: orderItem.total_price,
        tax_amount: orderItem.tax_amount,
        variant_options: orderItem.variant_options ?? {},
        requires_shipping: orderItem.requires_shipping,
        shipped_quantity: orderItem.shipped_quantity ?? 0,
    };
}

function OrderFormUnsavedAlert({ isDuplicate }: { isDuplicate: boolean }) {
    const form = useFormContext();
    const { confirm } = useConfirm();
    useUnsavedChangesAlert((form?.isDirty ?? false) || isDuplicate, { confirm });
    return null;
}

interface SourceOrderChange {
    title: string;
    type: 'updated' | 'removed';
}

interface OrderFormProps {
    order?: Order;
    sourceOrder?: Order;
    sourceOrderChanges?: SourceOrderChange[];
    paymentGateways: PaymentGateway[];
    displayTaxTotals: DisplayTaxTotals;
    storeCountryCode?: string | null;
}

export function OrderForm({
    order,
    sourceOrder,
    sourceOrderChanges,
    paymentGateways,
    displayTaxTotals,
    storeCountryCode,
}: OrderFormProps) {
    const source = order ?? sourceOrder;
    const isEditing = !!order;
    const defaultAddress = storeCountryCode ? ({ country_code: storeCountryCode } as OrderAddress) : null;
    const [items, setItems] = useState(() => source?.items?.map(toFormItems) ?? []);
    const [restock, setRestock] = useState(true);
    const [customerEmail, setCustomerEmail] = useState(source?.customer_email || '');
    const [shippingAddress, setShippingAddress] = useState(source?.shipping_address ?? defaultAddress);
    const [differentBillingAddress, setDifferentBillingAddress] = useState(
        !!source?.billing_address && !!source?.shipping_address,
    );
    const [billingAddress, setBillingAddress] = useState(source?.billing_address ?? defaultAddress);
    const [shippingRateId, setShippingRateId] = useState(isEditing ? String(source?.shipping_rate_id ?? '') : '');

    const [calculating, setCalculating] = useState(false);
    const [subtotal, setSubtotal] = useState(source?.subtotal || '0.0000');
    const [shipping, setShipping] = useState(isEditing ? source?.shipping_total || '0.0000' : '0.0000');
    const [discount, setDiscount] = useState(isEditing ? source?.discount_total || '0.0000' : '0.0000');
    const [tax, setTax] = useState(isEditing ? source?.tax_total || '0.0000' : '0.0000');
    const [taxDetails, setTaxDetails] = useState<TaxDetail[]>(() =>
        isEditing ? aggregateTaxDetails(source?.tax_details ?? []) : [],
    );
    const [total, setTotal] = useState(isEditing ? source?.total || '0.0000' : source?.subtotal || '0.0000');

    const { fieldRules: shippingRules, loading: shippingRulesLoading } = useAddressFieldRules(
        shippingAddress?.country_code ?? '',
    );
    const { fieldRules: billingRules, loading: billingRulesLoading } = useAddressFieldRules(
        billingAddress?.country_code ?? '',
    );

    const requiresShipping = items.length === 0 || items.some((item) => item.requires_shipping);

    const restockItemCount = !isEditing
        ? 0
        : (order?.items ?? []).reduce((total, orderItem) => {
              const stockTarget = orderItem.product_variant ?? orderItem.product;

              if (!stockTarget?.track_stock) {
                  return total;
              }

              const currentItem = items.find((item) => item.id === String(orderItem.id));
              const currentQuantity = currentItem ? Number(currentItem.quantity) : 0;
              const reduction = orderItem.quantity - currentQuantity;

              return reduction > 0 ? total + reduction : total;
          }, 0);

    const shippingAddressReady =
        shippingAddress != null && !shippingRulesLoading && isAddressReadyForRates(shippingAddress, shippingRules);
    const billingAddressReady =
        billingAddress != null && !billingRulesLoading && isAddressReadyForRates(billingAddress, billingRules);

    const addressReady = requiresShipping
        ? shippingAddressReady && (!differentBillingAddress || billingAddressReady)
        : billingAddressReady;

    const addressRateKey = requiresShipping
        ? `${rateRelevantAddressKey(shippingAddress)}::${differentBillingAddress ? rateRelevantAddressKey(billingAddress) : ''}`
        : `::${rateRelevantAddressKey(billingAddress)}`;

    const formatId = useFormatId();
    const formatDate = useFormatDate();
    const abortControllerRef = useRef<AbortController | null>(null);
    const formDirtyTriggerRef = useRef<HTMLInputElement>(null);
    const orderId = order?.id;

    const recalculate = useCallback(async () => {
        abortControllerRef.current?.abort();

        if (!items.length) {
            setSubtotal('0.0000');
            setShipping('0.0000');
            setDiscount('0.0000');
            setTax('0.0000');
            setTaxDetails([]);
            setTotal('0.0000');
            return;
        }

        const subtotalValue = sum(items.map((item) => item.total_price));
        setSubtotal(subtotalValue);

        const validItems = items.filter((item) => Number(item.quantity) > 0 && isPositive(item.unit_price));

        let taxValue = '0.0000';
        let taxDetailsValue: TaxDetail[] = [];
        if (validItems.length && addressReady) {
            abortControllerRef.current = new AbortController();

            try {
                setCalculating(true);

                const data = await httpPost<{ tax_total: string; tax_details: TaxDetail[] }>(
                    OrderTaxCalculatorController(),
                    {
                        order_id: orderId,
                        items: validItems,
                        subtotal: subtotalValue,
                        discount_total: discount,
                        shipping_total: shipping,
                        shipping_address: shippingAddress,
                        use_different_address: differentBillingAddress,
                        billing_address: billingAddress,
                    },
                    { signal: abortControllerRef.current.signal },
                );

                taxValue = data.tax_total;
                taxDetailsValue = data.tax_details;
                setCalculating(false);
                abortControllerRef.current = null;
            } catch (error) {
                if (isAbortError(error)) {
                    setCalculating(false);
                    abortControllerRef.current = null;
                    return;
                }
                if (isHttpError(error) && error.data?.message) {
                    if (error.status !== 422) {
                        toast.error(error.data.message as string);
                    }
                }
                setCalculating(false);
                abortControllerRef.current = null;
            }
        }

        setTax(taxValue);
        setTaxDetails(taxDetailsValue);

        setTotal(add(subtract(subtotalValue, discount), add(shipping, taxValue)));
    }, [orderId, items, shipping, discount, shippingAddress, differentBillingAddress, billingAddress, addressReady]);

    const debouncedRecalculate = useDebounce(recalculate, 300);

    const prevDepsRef = useRef<unknown[] | undefined>(undefined);
    useEffect(() => {
        const deps = [
            items,
            shipping,
            discount,
            addressRateKey,
            differentBillingAddress,
            addressReady,
            debouncedRecalculate,
        ];
        const prev = prevDepsRef.current;
        prevDepsRef.current = deps;

        if (!prev || prev.every((v, i) => v === deps[i])) return;

        debouncedRecalculate();

        return () => abortControllerRef.current?.abort();
    }, [items, shipping, discount, addressRateKey, differentBillingAddress, addressReady, debouncedRecalculate]);

    const handleItemsChange = (updatedItems: OrderItemForm[]): void => {
        setItems(updatedItems);
        formDirtyTriggerRef.current?.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const handleShippingRateChange = (rateId: string, rate: string): void => {
        setShippingRateId(rateId);
        setShipping(rate);
    };

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.items = data.items ?? [];
        data.different_billing_address = requiresShipping ? differentBillingAddress : false;
        data.shipping_rate_id = shippingRateId || null;
        data.add_more = data.add_more === 'on';
        data.notify_customer = data.notify_customer === 'on';
        data.restock = restock;
        return data;
    };

    return (
        <>
            <Heading
                pageTitle={order ? `${formatId(order.id)} - ${__('Edit order')}` : __('Add order')}
                title={order ? __('Edit order') : __('Add order')}
                description={
                    order
                        ? __('Last updated on :datetime', { datetime: formatDate(order.updated_at) })
                        : __('Add a new order to your store')
                }
                backHref={order ? OrderController.show(order) : OrderController.index()}
            />

            <Form
                {...(order ? OrderController.update.form({ order: order.id }) : OrderController.store.form())}
                options={{ preserveScroll: true }}
                transform={handleTransform}
                resetOnSuccess={!order}
                setDefaultsOnSuccess
                className="space-y-6"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <input ref={formDirtyTriggerRef} type="hidden" />
                        <OrderFormUnsavedAlert isDuplicate={!!sourceOrder} />
                        {sourceOrderChanges && sourceOrderChanges.length > 0 && (
                            <Alert variant="warning">
                                <TriangleAlertIcon />
                                <AlertTitle>{__('Some products have changed since this order was placed:')}</AlertTitle>
                                <AlertDescription>
                                    <ul className="list-disc ps-5">
                                        {sourceOrderChanges.map((change) => (
                                            <li key={`${change.title}-${change.type}`}>
                                                {change.type === 'removed'
                                                    ? __(':product was removed', { product: change.title })
                                                    : __(':product was updated', { product: change.title })}
                                            </li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div className="space-y-6 lg:col-span-2">
                                <OrderFormItems
                                    errors={errors}
                                    items={items}
                                    onItemsChange={handleItemsChange}
                                    exchangeRate={order?.exchange_rate}
                                    currencyCode={order?.currency_code}
                                    restockItemCount={restockItemCount}
                                    restock={restock}
                                    onRestockChange={setRestock}
                                />
                                <OrderFormCustomer
                                    order={source}
                                    customerEmail={customerEmail}
                                    onCustomerEmailChange={setCustomerEmail}
                                    errors={errors}
                                />
                                {requiresShipping ? (
                                    <>
                                        <OrderFormAddress
                                            type="shipping"
                                            address={shippingAddress}
                                            errors={errors}
                                            onAddressChange={setShippingAddress}
                                        />
                                        <OrderFormAddress
                                            type="billing"
                                            address={billingAddress}
                                            errors={errors}
                                            onAddressChange={setBillingAddress}
                                            differentBillingAddress={differentBillingAddress}
                                            onDifferentBillingAddressChange={setDifferentBillingAddress}
                                        />
                                    </>
                                ) : (
                                    <OrderFormAddress
                                        type="billing"
                                        address={billingAddress}
                                        errors={errors}
                                        onAddressChange={setBillingAddress}
                                        primary
                                    />
                                )}
                            </div>
                            <div className="space-y-6">
                                <OrderFormNotes order={source} errors={errors} />
                                {requiresShipping && (
                                    <OrderFormShipping
                                        items={items}
                                        shippingAddress={shippingAddress}
                                        differentBillingAddress={differentBillingAddress}
                                        billingAddress={billingAddress}
                                        addressReady={addressReady}
                                        addressKey={addressRateKey}
                                        shippingRateId={shippingRateId}
                                        onShippingRateChange={handleShippingRateChange}
                                        errors={errors}
                                    />
                                )}
                                <OrderFormPayment order={order} paymentGateways={paymentGateways} errors={errors} />
                                <OrderFormCoupon
                                    order={order}
                                    customerEmail={customerEmail}
                                    subtotal={subtotal}
                                    onDiscountChange={setDiscount}
                                />
                                <OrderFormSummary
                                    calculating={calculating}
                                    subtotal={subtotal}
                                    tax={tax}
                                    taxDetails={displayTaxTotals === 'itemized' ? taxDetails : []}
                                    shipping={shipping}
                                    showShipping={requiresShipping}
                                    discount={discount}
                                    total={total}
                                    currencyCode={order?.currency_code}
                                />
                                {order && (
                                    <Field orientation="horizontal">
                                        <Checkbox id="notify_customer" name="notify_customer" />
                                        <FieldLabel htmlFor="notify_customer" className="font-normal">
                                            {__('Notify customer')}
                                        </FieldLabel>
                                    </Field>
                                )}
                                <FormSubmit
                                    showAddMore={!order}
                                    processing={processing}
                                    recentlySuccessful={recentlySuccessful}
                                >
                                    {order ? __('Update order') : __('Add order')}
                                </FormSubmit>
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
