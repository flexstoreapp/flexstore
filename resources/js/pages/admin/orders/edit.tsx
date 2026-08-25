import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { OrderForm } from '@/components/admin/order/order-form';
import { __ } from '@/lib/i18n';
import type { DisplayTaxTotals, Order, PaymentGateway } from '@/types';

interface OrderEditProps {
    order: Order;
    paymentGateways: PaymentGateway[];
    displayTaxTotals: DisplayTaxTotals;
}

export default function OrderEdit({ order, paymentGateways, displayTaxTotals }: OrderEditProps) {
    return <OrderForm order={order} paymentGateways={paymentGateways} displayTaxTotals={displayTaxTotals} />;
}

OrderEdit.layout = ({ order }: OrderEditProps) => ({
    breadcrumbs: [
        { title: __('Orders'), href: OrderController.index() },
        { title: `#${order.id}`, href: OrderController.show(order) },
        { title: __('Edit order'), href: OrderController.edit(order) },
    ],
});
