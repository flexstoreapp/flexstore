<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\Address;
use App\DTOs\CouponValidationResult;
use App\DTOs\HydratedOrderItem;
use App\DTOs\OrderItemInput;
use App\DTOs\StockAdjustmentInput;
use App\DTOs\StoreOrderInput;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Enums\TaxBasedOn;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Utilities\CouponValidator;
use App\Utilities\CurrencyConverter;
use App\Utilities\OrderUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreOrderAction
{
    public function __construct(
        private OrderUtility $orderUtility,
        private CouponValidator $couponValidator,
        private CurrencyConverter $currencyConverter,
        private StoreOrderItemAction $storeOrderItemAction,
        private AdjustStockAction $adjustStockAction,
        private IncrementCouponUsageAction $incrementCouponUsageAction,
        private UpsertOrderAddressAction $upsertOrderAddressAction,
        private RecalculateOrderTotalsAction $recalculateOrderTotalsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private SendAdminNotificationAction $sendAdminNotificationAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
    ) {
    }

    public function handle(StoreOrderInput $input, ?User $user = null): Order
    {
        $order = DB::transaction(function () use ($input, $user): Order {
            $currencyCode = $input->currencyCode ?? Setting::getValue('base_currency');
            $decimalPlaces = Currency::getDecimalPlaces($currencyCode);
            $scaleFn = fn (string $amount): string => $this->currencyConverter->round($amount, $decimalPlaces);

            $itemsData = array_map(fn (OrderItemInput $item): array => $item->toArray(), $input->items);
            $hydratedItems = $this->orderUtility->hydrateItems($itemsData);
            $hydratedItems = array_map(
                fn (HydratedOrderItem $item): HydratedOrderItem => $item->withPrices(
                    $scaleFn($item->unitPrice),
                    $scaleFn($item->totalPrice),
                ),
                $hydratedItems,
            );

            $subtotal = OrderUtility::calculateSubtotal($hydratedItems);
            $couponValidationResult = $this->validateCoupon($input, $subtotal);
            $order = $this->storeOrder($input, $subtotal, $couponValidationResult, $currencyCode, $decimalPlaces);

            foreach ($hydratedItems as $hydratedItem) {
                $orderItem = $this->storeOrderItemAction->handle(
                    $order,
                    $hydratedItem->product,
                    $hydratedItem->variant,
                    $hydratedItem,
                );

                $this->decrementStock($orderItem, $user);
            }

            if ($couponValidationResult instanceof CouponValidationResult) {
                $this->incrementCouponUsageAction->handle($couponValidationResult->coupon);
            }

            if ($input->shippingAddress instanceof Address) {
                $this->upsertOrderAddressAction->handle($order, $input->shippingAddress, OrderAddressType::Shipping);
            }

            $billingAddress = $input->differentBillingAddress && $input->billingAddress instanceof Address
                ? $input->billingAddress
                : ($input->shippingAddress ?? $input->billingAddress);

            if ($billingAddress instanceof Address) {
                $this->upsertOrderAddressAction->handle($order, $billingAddress, OrderAddressType::Billing);
            }

            $this->recalculateOrderTotalsAction->handle($order);

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::OrderPlaced,
                user: $user,
            );

            return $order;
        });

        if (Setting::getValue('notification_admin_new_order')) {
            $this->sendAdminNotificationAction->handle(new AdminNewOrderNotification($order));
        }

        if (Setting::getValue('notification_customer_order_confirmed')) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerOrderConfirmedNotification($order),
                $order->customer_id !== null ? $order->customer : null,
                $order->customer_email,
            );
        }

        return $order;
    }

    /**
     * @throws ValidationException
     */
    private function validateCoupon(StoreOrderInput $input, string $subtotal): ?CouponValidationResult
    {
        if ($input->couponCode === null || $input->couponCode === '') {
            return null;
        }

        return $this->couponValidator->validate(
            $input->couponCode,
            $subtotal,
            $input->customerEmail,
        );
    }

    /**
     * @param  int<0, max>  $decimalPlaces
     */
    private function storeOrder(StoreOrderInput $input, string $subtotal, ?CouponValidationResult $couponValidationResult, string $currencyCode, int $decimalPlaces): Order
    {
        $shippingRate = $input->shippingRateId !== null
            ? ShippingRate::query()->with('carrier:id,name')->find($input->shippingRateId)
            : null;
        $paymentGateway = $input->paymentGatewayId !== null
            ? PaymentGateway::query()->find($input->paymentGatewayId, ['name'])
            : null;

        return Order::query()->create([
            'customer_id' => $input->customerId,
            'payment_status' => PaymentStatus::Unpaid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'customer_email' => $input->customerEmail,
            'prices_include_tax' => (bool) Setting::getValue('prices_include_tax'),
            'shipping_is_taxable' => (bool) Setting::getValue('shipping_is_taxable'),
            'tax_based_on' => Setting::getValue('tax_based_on') ?? TaxBasedOn::Shipping->value,
            'default_tax_rate' => Setting::getValue('default_tax_rate'),
            'tax_store_country_code' => Setting::getValue('store_country_code'),
            'tax_store_state' => Setting::getValue('store_state'),
            'tax_store_postal_code' => Setting::getValue('store_postal_code'),
            'subtotal' => $subtotal,
            'tax_total' => '0.0000',
            'shipping_total' => $this->currencyConverter->round($shippingRate->rate ?? '0.0000', $decimalPlaces),
            'discount_total' => $this->currencyConverter->round($couponValidationResult->discount ?? '0.0000', $decimalPlaces),
            'total' => '0.0000',
            'currency_code' => $currencyCode,
            'exchange_rate' => Currency::getExchangeRate($currencyCode),
            'shipping_carrier_id' => $shippingRate?->shipping_carrier_id,
            'shipping_carrier_name' => $shippingRate?->carrier?->getTranslations('name'),
            'shipping_rate_id' => $input->shippingRateId,
            'shipping_rate_name' => $shippingRate?->getTranslations('name'),
            'payment_gateway_id' => $input->paymentGatewayId,
            'payment_gateway_name' => $paymentGateway?->getTranslations('name'),
            'coupon_id' => $couponValidationResult?->coupon->id,
            'coupon_code' => $couponValidationResult?->coupon->code,
            'notes' => $input->notes,
        ]);
    }

    private function decrementStock(OrderItem $orderItem, ?User $user): void
    {
        $orderItem->loadMissing(['product', 'productVariant']);

        $product = $orderItem->product;

        if (! $product) {
            return;
        }

        $target = $orderItem->productVariant ?? $product;

        if (! $target->track_stock) {
            return;
        }

        $this->adjustStockAction->handle($user, $product, $orderItem->productVariant, StockAdjustmentInput::fromArray([
            'quantity' => -$orderItem->quantity,
            'reason' => StockMovementReason::Sale,
            'reference_type' => Order::class,
            'reference_id' => $orderItem->order_id,
        ]));
    }
}
