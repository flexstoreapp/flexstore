<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\Address;
use App\DTOs\HydratedOrderItem;
use App\DTOs\PaymentSettlement;
use App\DTOs\StockAdjustmentInput;
use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Enums\TaxBasedOn;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WeightUnit;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\CustomerDigitalDownloadsReadyNotification;
use App\Notifications\CustomerOrderConfirmedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class ConfirmCheckoutSessionAction
{
    public function __construct(
        private StoreOrderItemAction $storeOrderItemAction,
        private AdjustStockAction $adjustStockAction,
        private IncrementCouponUsageAction $incrementCouponUsageAction,
        private UpsertOrderAddressAction $upsertOrderAddressAction,
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private RecalculateOrderTotalsAction $recalculateOrderTotalsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private CancelCheckoutSessionAction $cancelCheckoutSessionAction,
        private ReleaseStockReservationsAction $releaseStockReservationsAction,
        private ClearCartAction $clearCartAction,
        private SendAdminNotificationAction $sendAdminNotificationAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
        private FulfillDigitalItemsAction $fulfillDigitalItemsAction,
    ) {
    }

    public function handle(CheckoutSession $session, ?PaymentSettlement $settlement = null): ?Order
    {
        $settlement ??= new PaymentSettlement(status: PaymentStatus::Paid);

        if (in_array($settlement->status, [PaymentStatus::Failed, PaymentStatus::Canceled], true)) {
            $this->cancelCheckoutSessionAction->handle($session);

            return null;
        }

        $isNewOrder = false;
        $notifyDownloadsReady = false;

        $order = DB::transaction(function () use ($session, $settlement, &$isNewOrder, &$notifyDownloadsReady): Order {
            $session = CheckoutSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status === CheckoutSessionStatus::Completed && $session->order_id !== null) {
                $order = Order::query()->findOrFail($session->order_id);

                if ($settlement->gatewayReference !== null && $order->payment_status !== $settlement->status) {
                    $from = $order->payment_status;

                    $transaction = $this->storeOrderTransaction($order, $settlement);

                    if ($transaction instanceof OrderTransaction) {
                        $order = $this->reconcileOrderFinancialsAction->handle($order);
                    } else {
                        $order->update(['payment_status' => $settlement->status]);
                    }

                    $this->recordPaymentActivity($order, $from, $transaction);
                    $this->fulfillDigitalItemsAction->handle($order->refresh());

                    $notifyDownloadsReady = $order->payment_status === PaymentStatus::Paid
                        && $order->itemDownloads()->exists();
                }

                return $order->refresh();
            }

            if (! in_array($session->status, [CheckoutSessionStatus::Pending, CheckoutSessionStatus::Canceled], true)) {
                throw new RuntimeException("Cannot confirm checkout session in {$session->status->value} status.");
            }

            if ($session->step !== CheckoutStep::PaymentInitiated) {
                throw new RuntimeException("Cannot confirm checkout session in {$session->step?->value} step.");
            }

            $customer = $session->customer_id !== null
                ? User::query()->find($session->customer_id)
                : null;

            if ($session->coupon_id !== null) {
                $coupon = Coupon::query()->lockForUpdate()->find($session->coupon_id);

                if ($coupon !== null) {
                    $this->incrementCouponUsageAction->handle($coupon);
                }
            }

            $order = $this->storeOrderFromSession($session);
            $this->storeOrderItems($order, $session, $customer);
            $this->storeAddresses($order, $session);
            $this->recalculateOrderTotalsAction->handle($order);
            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::OrderPlaced,
                user: $customer,
            );

            $this->releaseStockReservationsAction->handle($session);
            $this->clearSessionCart($session);

            $session->update([
                'status' => CheckoutSessionStatus::Completed,
                'completed_at' => now(),
                'order_id' => $order->id,
            ]);

            $from = $order->payment_status;
            $transaction = $this->storeOrderTransaction($order, $settlement);

            if ($transaction instanceof OrderTransaction) {
                $order = $this->reconcileOrderFinancialsAction->handle($order);
            } else {
                $order->update(['payment_status' => $settlement->status]);
            }

            $this->recordPaymentActivity($order, $from, $transaction, $customer);

            $this->fulfillDigitalItemsAction->handle($order->refresh());

            $isNewOrder = true;

            return $order->refresh();
        });

        if ($isNewOrder) {
            if (Setting::getValue('notification_admin_new_order')) {
                $this->sendAdminNotificationAction->handle(new AdminNewOrderNotification($order));
            }

            if (Setting::getValue('notification_customer_order_confirmed')) {
                $this->sendCustomerNotificationAction->handle(
                    new CustomerOrderConfirmedNotification($order),
                    $order->customer_id ? $order->customer : null,
                    $order->customer_email,
                );
            }
        }

        if ($notifyDownloadsReady && Setting::getValue('notification_customer_order_confirmed')) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerDigitalDownloadsReadyNotification($order),
                $order->customer_id ? $order->customer : null,
                $order->customer_email,
            );
        }

        return $order;
    }

    private function storeOrderFromSession(CheckoutSession $session): Order
    {
        return Order::query()->create([
            'customer_id' => $session->customer_id,
            'payment_status' => PaymentStatus::Unpaid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'customer_email' => $session->customer_email,
            'prices_include_tax' => $session->prices_include_tax ?? false,
            'shipping_is_taxable' => $session->shipping_is_taxable ?? false,
            'tax_based_on' => $session->tax_based_on ?? TaxBasedOn::Shipping->value,
            'default_tax_rate' => $session->default_tax_rate,
            'tax_store_country_code' => $session->tax_store_country_code,
            'tax_store_state' => $session->tax_store_state,
            'tax_store_postal_code' => $session->tax_store_postal_code,
            'subtotal' => $session->subtotal,
            'tax_total' => '0.0000',
            'shipping_total' => $session->shipping_total,
            'discount_total' => $session->discount_total,
            'total' => '0.0000',
            'currency_code' => $session->currency_code,
            'exchange_rate' => $session->exchange_rate,
            'shipping_carrier_id' => $session->shipping_carrier_id,
            'shipping_carrier_name' => $session->getTranslations('shipping_carrier_name'),
            'shipping_rate_id' => $session->shipping_rate_id,
            'shipping_rate_name' => $session->getTranslations('shipping_rate_name'),
            'region_id' => $session->region_id,
            'region_name' => $session->getTranslations('region_name') ?: null,
            'payment_gateway_id' => $session->payment_gateway_id,
            'payment_gateway_name' => $session->getTranslations('payment_gateway_name'),
            'coupon_id' => $session->coupon_id,
            'coupon_code' => $session->coupon_code,
            'notes' => $session->notes,
        ]);
    }

    private function storeOrderItems(Order $order, CheckoutSession $session, ?User $customer): void
    {
        $items = $session->items;
        assert($items !== null);

        $productIds = array_column($items, 'product_id');
        $variantIds = array_filter(array_column($items, 'product_variant_id'));

        $products = Product::query()
            ->withFeaturedMedia()
            ->findMany($productIds)->keyBy('id');
        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->with(['media:' . Media::displaySelect()])
                ->findMany($variantIds)->keyBy('id');

        usort($items, fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $product = $products[$item['product_id']];
            assert($product instanceof Product);

            $variant = isset($item['product_variant_id'])
                ? $variants[$item['product_variant_id']]
                : null;
            assert($variant === null || $variant instanceof ProductVariant);

            $orderItem = $this->storeOrderItemAction->handle(
                $order,
                $product,
                $variant,
                new HydratedOrderItem(
                    product: $product,
                    variant: $variant,
                    unitPrice: (string) $item['unit_price'],
                    totalPrice: (string) $item['total_price'],
                    quantity: (int) $item['quantity'],
                    variantOptions: is_array($item['variant_options'] ?? null) ? $item['variant_options'] : null,
                    productTitle: is_array($item['product_title'] ?? null) ? $item['product_title'] : ['en' => (string) ($item['product_title'] ?? '')],
                    productSku: (string) ($item['product_sku'] ?? ''),
                    variantTitle: isset($item['variant_title']) ? (string) $item['variant_title'] : null,
                    requiresShipping: (bool) ($item['requires_shipping'] ?? false),
                    weight: isset($item['weight']) ? (string) $item['weight'] : null,
                    weightUnit: isset($item['weight_unit']) ? WeightUnit::from((string) $item['weight_unit']) : null,
                    length: $variant->length ?? $product->length,
                    width: $variant->width ?? $product->width,
                    height: $variant->height ?? $product->height,
                    dimensionUnit: $variant->dimension_unit ?? $product->dimension_unit,
                ),
            );

            $this->decrementStock($orderItem->order_id, $customer, $product, $variant, $item['quantity']);
        }
    }

    private function decrementStock(int $orderId, ?User $customer, Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $target = $variant ?? $product;

        if (! $target->track_stock) {
            return;
        }

        $this->adjustStockAction->handle($customer, $product, $variant, StockAdjustmentInput::fromArray([
            'quantity' => -$quantity,
            'reason' => StockMovementReason::Sale,
            'reference_type' => Order::class,
            'reference_id' => $orderId,
        ]), allowOversell: true);
    }

    private function storeAddresses(Order $order, CheckoutSession $session): void
    {
        $shippingAddress = $session->shipping_address;

        if ($shippingAddress !== null) {
            $shippingInput = Address::fromArray($shippingAddress);
            $this->upsertOrderAddressAction->handle($order, $shippingInput, OrderAddressType::Shipping);

            $billingInput = $session->different_billing_address && $session->billing_address !== null
                ? Address::fromArray($session->billing_address)
                : $shippingInput;

            $this->upsertOrderAddressAction->handle($order, $billingInput, OrderAddressType::Billing);

            return;
        }

        if ($session->billing_address !== null) {
            $this->upsertOrderAddressAction->handle(
                $order,
                Address::fromArray($session->billing_address),
                OrderAddressType::Billing,
            );
        }
    }

    private function storeOrderTransaction(Order $order, PaymentSettlement $settlement): ?OrderTransaction
    {
        if (in_array($settlement->status, [PaymentStatus::Unpaid, PaymentStatus::Failed, PaymentStatus::Canceled], true)) {
            return null;
        }

        $order->loadMissing('paymentGateway');

        if ($order->paymentGateway === null || $order->usesManualPayment()) {
            return null;
        }

        return $this->storeOrderTransactionAction->handle(
            order: $order,
            type: TransactionType::Sale,
            status: TransactionStatus::Success,
            amount: $order->total,
            gatewayReference: $settlement->gatewayReference,
            paymentMethod: $settlement->paymentMethod,
            paymentMethodDetails: $settlement->paymentMethodDetails,
        );
    }

    private function recordPaymentActivity(
        Order $order,
        PaymentStatus $from,
        ?OrderTransaction $transaction,
        ?User $user = null,
    ): void {
        if ($from === $order->payment_status) {
            return;
        }

        $isSale = $transaction instanceof OrderTransaction
            && $transaction->type === TransactionType::Sale;

        $this->storeOrderActivityAction->handle(
            order: $order,
            type: $isSale ? OrderActivityType::PaymentReceived : OrderActivityType::PaymentStatusChanged,
            user: $user,
            metadata: array_filter([
                'transaction_id' => $transaction?->id,
                'from_status' => $from->value,
                'to_status' => $order->payment_status->value,
            ]),
        );
    }

    private function clearSessionCart(CheckoutSession $session): void
    {
        $cart = $session->cart()->first();

        if ($cart instanceof Cart) {
            $this->clearCartAction->handle($cart);
        }
    }
}
