import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { OrderForm } from '@/components/admin/order/order-form';
import { __ } from '@/lib/i18n';
import type { DisplayTaxTotals, Order, PaymentGateway } from '@/types';

interface SourceOrderChange {
    title: string;
    type: 'updated' | 'removed';
}

interface OrderCreateProps {
    sourceOrder?: Order;
    sourceOrderChanges?: SourceOrderChange[];
    paymentGateways: PaymentGateway[];
    displayTaxTotals: DisplayTaxTotals;
    storeCountryCode: string | null;
}

export default function OrderCreate({
    sourceOrder,
    sourceOrderChanges,
    paymentGateways,
    displayTaxTotals,
    storeCountryCode,
}: OrderCreateProps) {
    return (
        <OrderForm
            sourceOrder={sourceOrder}
            sourceOrderChanges={sourceOrderChanges}
            paymentGateways={paymentGateways}
            displayTaxTotals={displayTaxTotals}
            storeCountryCode={storeCountryCode}
        />
    );
}

OrderCreate.layout = {
    breadcrumbs: [
        { title: __('Orders'), href: OrderController.index() },
        { title: __('Add order'), href: OrderController.create() },
    ],
};
