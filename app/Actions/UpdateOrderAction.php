<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\Address;
use App\DTOs\HydratedOrderItem;
use App\DTOs\OrderItemInput;
use App\DTOs\StockAdjustmentInput;
use App\DTOs\UpdateOrderInput;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\StockMovementReason;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use App\Utilities\CouponDiscountCalculator;
use App\Utilities\CouponValidator;
use App\Utilities\CurrencyConverter;
use App\Utilities\OrderUtility;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOrderAction
{
    public function __construct(
        private OrderUtility $orderUtility,
        private CouponValidator $couponValidator,
        private CouponDiscountCalculator $couponDiscountCalculator,
        private CurrencyConverter $currencyConverter,
        private IncrementCouponUsageAction $incrementCouponUsageAction,
        private DecrementCouponUsageAction $decrementCouponUsageAction,
        private AdjustStockAction $adjustStockAction,
        private StoreOrderItemAction $storeOrderItemAction,
        private UpdateOrderItemAction $updateOrderItemAction,
        private RecalculateOrderTotalsAction $recalculateOrderTotalsAction,
        private UpsertOrderAddressAction $upsertOrderAddressAction,
        private RecalculateCustomerLifetimeValueAction $recalculateCustomerLifetimeValueAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private ReconcileFulfillmentStatusAction $reconcileFulfillmentStatusAction,
    ) {
    }

    public function handle(User $user, Order $order, UpdateOrderInput $input): Order
    {
        return DB::transaction(function () use ($user, $order, $input): Order {
            $originalCustomerId = $order->customer_id;
            $originalCouponId = $order->coupon_id;
            $itemsChanged = $input->has('items') && $input->items !== null;
            $shippingChanged = $input->has('shipping_rate_id') && $input->shippingRateId !== $order->shipping_rate_id;
            $currencyChanged = $input->has('currency_code') && $input->currencyCode !== $order->currency_code;

            $snapshot = $this->captureSnapshot($order, $input);

            if ($itemsChanged) {
                $currencyCode = $input->has('currency_code') && $input->currencyCode !== null ? $input->currencyCode : $order->currency_code;
                $this->updateOrderItems($user, $order, $input->items ?? [], $currencyCode, $input->restock ?? false);
                $order->load('items');
                $order->update(['subtotal' => OrderUtility::calculateSubtotal($order->items)]);

                if (! $order->requiresShipping()) {
                    $order->shippingAddress()->delete();
                    $order->setRelation('shippingAddress', null);
                }

                $this->reconcileFulfillmentStatusAction->handle($order);
            }

            $couponData = $this->processCoupon($order, $input, $itemsChanged);
            $this->updateOrder($order, $input, $couponData);
            $this->handleCouponUsageChanges($order, $originalCouponId, $couponData);

            if ($currencyChanged && ! $itemsChanged) {
                $this->rescaleItemPrices($order);
            }

            if ($input->shippingAddress instanceof Address) {
                $this->upsertOrderAddressAction->handle($order, $input->shippingAddress, OrderAddressType::Shipping);
            }

            if ($input->has('different_billing_address')) {
                if ($input->differentBillingAddress) {
                    if ($input->billingAddress instanceof Address) {
                        $this->upsertOrderAddressAction->handle($order, $input->billingAddress, OrderAddressType::Billing);
                    }
                } else {
                    $shippingAddress = $order->shippingAddress;

                    if ($shippingAddress !== null) {
                        $this->upsertOrderAddressAction->handle($order, Address::fromArray($shippingAddress->toArray()), OrderAddressType::Billing);
                    } elseif ($input->billingAddress instanceof Address) {
                        $this->upsertOrderAddressAction->handle($order, $input->billingAddress, OrderAddressType::Billing);
                    }
                }
            }

            $requiresRecalculation = $itemsChanged || $shippingChanged || $currencyChanged || isset($couponData['discount']);

            if ($requiresRecalculation) {
                $this->recalculateTotals($order);
            }

            $this->recalculateCustomerLifetimeValue($order, $originalCustomerId);

            $this->recordEditActivity($user, $order, $snapshot);

            return $order;
        });
    }

    /**
     * @return array{coupon?: Coupon, coupon_id?: int|null, coupon_code?: string|null, discount?: string}
     */
    private function processCoupon(Order $order, UpdateOrderInput $input, bool $itemsChanged): array
    {
        $couponCodeProvided = $input->has('coupon_code');

        if ($couponCodeProvided && ($input->couponCode === null || $input->couponCode === '')) {
            return [
                'coupon_id' => null,
                'coupon_code' => null,
                'discount' => '0.0000',
            ];
        }

        $newCouponCode = $input->couponCode;
        $couponChanged = $couponCodeProvided && $newCouponCode !== $order->coupon_code;

        if ($couponChanged && $newCouponCode !== null) {
            return $this->validateNewCoupon($order, $input);
        }

        if ($itemsChanged && $order->coupon_id !== null) {
            return $this->recalculateExistingCouponDiscount($order);
        }

        return [];
    }

    /**
     * @return array{coupon: Coupon, coupon_id: int, coupon_code: string, discount: string}
     */
    private function validateNewCoupon(Order $order, UpdateOrderInput $input): array
    {
        assert($input->couponCode !== null);

        $result = $this->couponValidator->validate(
            $input->couponCode,
            $order->subtotal ?? '0.0000',
            $input->customerEmail ?? $order->customer_email
        );

        return [
            'coupon' => $result->coupon,
            'coupon_id' => $result->coupon->id,
            'coupon_code' => $result->coupon->code,
            'discount' => $result->discount,
        ];
    }

    /**
     * @return array{discount?: string}
     */
    private function recalculateExistingCouponDiscount(Order $order): array
    {
        $coupon = $order->coupon;

        if (! $coupon) {
            return [];
        }

        $discount = $this->couponDiscountCalculator->calculate($coupon, $order->subtotal);

        return ['discount' => $discount];
    }

    /**
     * @param  array{coupon?: Coupon, coupon_id?: int|null, coupon_code?: string|null, discount?: string}  $couponData
     */
    private function handleCouponUsageChanges(Order $order, ?int $originalCouponId, array $couponData): void
    {
        $newCouponId = $couponData['coupon_id'] ?? $order->coupon_id;

        if ($originalCouponId === $newCouponId) {
            return;
        }

        if ($originalCouponId !== null) {
            $oldCoupon = Coupon::query()->find($originalCouponId);
            if ($oldCoupon) {
                $this->decrementCouponUsageAction->handle($oldCoupon);
            }
        }

        if (isset($couponData['coupon'])) {
            $this->incrementCouponUsageAction->handle($couponData['coupon']);
        }
    }

    /**
     * @param  array{coupon?: Coupon, coupon_id?: int|null, coupon_code?: string|null, discount?: string}  $couponData
     */
    private function updateOrder(Order $order, UpdateOrderInput $input, array $couponData): void
    {
        $shippingRateId = $input->has('shipping_rate_id') ? $input->shippingRateId : $order->shipping_rate_id;
        $paymentGatewayId = $input->has('payment_gateway_id') ? $input->paymentGatewayId : $order->payment_gateway_id;
        $currencyCode = $input->has('currency_code') && $input->currencyCode !== null ? $input->currencyCode : $order->currency_code;

        $shippingRate = null;
        if ($input->has('shipping_rate_id') && $input->shippingRateId !== null) {
            $shippingRate = ShippingRate::query()->with('carrier:id,name')->find($input->shippingRateId);
        }

        $paymentGateway = null;
        if ($input->has('payment_gateway_id') && $input->paymentGatewayId !== null && $input->paymentGatewayId !== $order->payment_gateway_id) {
            $paymentGateway = PaymentGateway::query()->find($input->paymentGatewayId);
        }

        $shippingCarrierName = $shippingRate?->carrier?->getTranslations('name')
            ?? ($shippingRateId === $order->shipping_rate_id ? $order->getTranslations('shipping_carrier_name') : null);
        $shippingRateName = $shippingRate?->getTranslations('name')
            ?? ($shippingRateId === $order->shipping_rate_id ? $order->getTranslations('shipping_rate_name') : null);
        $paymentGatewayName = $paymentGateway?->getTranslations('name')
            ?? ($paymentGatewayId === $order->payment_gateway_id ? $order->getTranslations('payment_gateway_name') : null);

        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);
        $currencyChanged = $input->has('currency_code') && $input->currencyCode !== $order->currency_code;

        if (isset($couponData['discount'])) {
            $discountTotal = $this->currencyConverter->round($couponData['discount'], $decimalPlaces);
        } elseif ($currencyChanged) {
            $discountTotal = $this->currencyConverter->round($order->discount_total, $decimalPlaces);
        } else {
            $discountTotal = $order->discount_total;
        }

        $updateData = [
            'customer_id' => $input->has('customer_id') ? $input->customerId : $order->customer_id,
            'customer_email' => $input->has('customer_email') ? $input->customerEmail : $order->customer_email,
            'discount_total' => $discountTotal,
            'currency_code' => $currencyCode,
            'shipping_carrier_id' => $shippingRate->shipping_carrier_id ?? ($shippingRateId === $order->shipping_rate_id ? $order->shipping_carrier_id : null),
            'shipping_carrier_name' => $shippingCarrierName,
            'shipping_rate_id' => $shippingRateId,
            'shipping_rate_name' => $shippingRateName,
            'payment_gateway_id' => $paymentGatewayId,
            'payment_gateway_name' => $paymentGatewayName,
            'coupon_id' => array_key_exists('coupon_id', $couponData) ? $couponData['coupon_id'] : $order->coupon_id,
            'coupon_code' => array_key_exists('coupon_code', $couponData) ? $couponData['coupon_code'] : $order->coupon_code,
            'notes' => $input->has('notes') ? $input->notes : $order->notes,
        ];

        if ($input->has('shipping_rate_id')) {
            $updateData['shipping_total'] = $this->currencyConverter->round($shippingRate->rate ?? '0.0000', $decimalPlaces);
        } elseif ($currencyChanged) {
            $updateData['shipping_total'] = $this->currencyConverter->round($order->shipping_total, $decimalPlaces);
        }

        if ($currencyChanged) {
            $updateData['exchange_rate'] = Currency::getExchangeRate($currencyCode);
        }

        $order->update($updateData);
    }

    private function recalculateTotals(Order $order): void
    {
        $order->refresh();

        $this->recalculateOrderTotalsAction->handle($order);
    }

    /**
     * @param  list<OrderItemInput>  $items
     */
    private function updateOrderItems(User $user, Order $order, array $items, string $currencyCode, bool $restock): void
    {
        $itemsData = array_map(fn (OrderItemInput $i): array => $i->toArray(), $items);
        $hydratedItems = $this->orderUtility->hydrateItems($itemsData);

        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);
        $scaleFn = fn (string $amount): string => $this->currencyConverter->round($amount, $decimalPlaces);
        $hydratedItems = array_map(
            function (HydratedOrderItem $item) use ($scaleFn): HydratedOrderItem {
                $scaledUnitPrice = $scaleFn($item->unitPrice);

                return $item->withPrices(
                    $scaledUnitPrice,
                    BigDecimal::of($scaledUnitPrice)->multipliedBy($item->quantity)->toScale(4, RoundingMode::HalfUp)->toString(),
                );
            },
            $hydratedItems,
        );

        $existingItemIds = [];
        $existingItemsById = $order->items()->get()->keyBy('id');
        $stockChanges = [];

        foreach ($hydratedItems as $index => $hydratedItem) {
            $itemInput = $items[$index];

            if ($itemInput->id !== null) {
                $existingItem = $existingItemsById->get($itemInput->id);

                if (! $existingItem instanceof OrderItem) {
                    throw (new ModelNotFoundException)->setModel(OrderItem::class, [$itemInput->id]);
                }

                $quantityChange = $existingItem->quantity - $hydratedItem->quantity;

                $this->updateOrderItemAction->handle($existingItem, $hydratedItem->product, $hydratedItem->variant, $hydratedItem);
                $existingItemIds[] = $itemInput->id;
            } else {
                $newItem = $this->storeOrderItemAction->handle($order, $hydratedItem->product, $hydratedItem->variant, $hydratedItem);
                $existingItemIds[] = $newItem->id;

                $quantityChange = -$newItem->quantity;
            }

            $stockChanges = $this->accumulateStockChange($stockChanges, $hydratedItem->product, $hydratedItem->variant, $quantityChange);
        }

        $removedItems = $order->items()->with(['product', 'productVariant'])->whereNotIn('id', $existingItemIds)->get();
        foreach ($removedItems as $removedItem) {
            if ($removedItem->product !== null) {
                $stockChanges = $this->accumulateStockChange($stockChanges, $removedItem->product, $removedItem->productVariant, $removedItem->quantity);
            }
        }
        $order->items()->whereNotIn('id', $existingItemIds)->delete();

        foreach ($stockChanges as $stockChange) {
            $this->adjustStockForTarget($user, $stockChange['product'], $stockChange['variant'], $stockChange['quantity'], $restock, $order->id);
        }
    }

    /**
     * @param  array<string, array{product: Product, variant: ProductVariant|null, quantity: int}>  $stockChanges
     * @return array<string, array{product: Product, variant: ProductVariant|null, quantity: int}>
     */
    private function accumulateStockChange(array $stockChanges, Product $product, ?ProductVariant $variant, int $quantityChange): array
    {
        if ($quantityChange === 0) {
            return $stockChanges;
        }

        $key = $product->id . ':' . ($variant->id ?? '');

        $stockChanges[$key] = [
            'product' => $product,
            'variant' => $variant,
            'quantity' => ($stockChanges[$key]['quantity'] ?? 0) + $quantityChange,
        ];

        return $stockChanges;
    }

    private function adjustStockForTarget(User $user, Product $product, ?ProductVariant $variant, int $quantityChange, bool $restock, int $orderId): void
    {
        if ($quantityChange === 0 || ($quantityChange > 0 && ! $restock)) {
            return;
        }

        $target = $variant ?? $product;

        if (! $target->track_stock) {
            return;
        }

        $this->adjustStockAction->handle($user, $product, $variant, StockAdjustmentInput::fromArray([
            'quantity' => $quantityChange,
            'reason' => $quantityChange > 0 ? StockMovementReason::Return : StockMovementReason::Sale,
            'reference_type' => Order::class,
            'reference_id' => $orderId,
        ]));
    }

    private function rescaleItemPrices(Order $order): void
    {
        $decimalPlaces = Currency::getDecimalPlaces($order->currency_code);
        $scaleFn = fn (string $amount): string => $this->currencyConverter->round($amount, $decimalPlaces);

        foreach ($order->items()->get() as $item) {
            $scaledUnitPrice = $scaleFn($item->unit_price);
            $item->update([
                'unit_price' => $scaledUnitPrice,
                'total_price' => BigDecimal::of($scaledUnitPrice)->multipliedBy($item->quantity)->toScale(4, RoundingMode::HalfUp)->toString(),
            ]);
        }

        $order->load('items');
        $order->update(['subtotal' => OrderUtility::calculateSubtotal($order->items)]);
    }

    private function recalculateCustomerLifetimeValue(Order $order, ?int $originalCustomerId): void
    {
        if ($originalCustomerId !== null && $originalCustomerId !== $order->customer_id) {
            $originalCustomer = User::query()->find($originalCustomerId);

            if ($originalCustomer instanceof User) {
                $this->recalculateCustomerLifetimeValueAction->handle($originalCustomer);
            }
        }

        if ($order->customer_id !== null) {
            $customer = User::query()->find($order->customer_id);

            if ($customer instanceof User) {
                $this->recalculateCustomerLifetimeValueAction->handle($customer);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function captureSnapshot(Order $order, UpdateOrderInput $input): array
    {
        $snapshot = [];

        if ($input->has('items')) {
            $snapshot['items'] = $order->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'title' => $item->getTranslations('product_title'),
                'variant' => $item->variant_title,
                'quantity' => $item->quantity,
            ])->all();
        }

        if ($input->has('customer_id') || $input->has('customer_email')) {
            $snapshot['customer_email'] = $order->customer_email;
            $snapshot['customer_id'] = $order->customer_id;
        }

        if ($input->has('shipping_rate_id')) {
            $snapshot['shipping_rate_id'] = $order->shipping_rate_id;
            $snapshot['shipping_rate_name'] = $order->getTranslations('shipping_rate_name');
        }

        if ($input->has('payment_gateway_id')) {
            $snapshot['payment_gateway_id'] = $order->payment_gateway_id;
            $snapshot['payment_gateway_name'] = $order->getTranslations('payment_gateway_name');
        }

        if ($input->has('coupon_code')) {
            $snapshot['coupon_code'] = $order->coupon_code;
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function recordEditActivity(User $user, Order $order, array $snapshot): void
    {
        $changes = [];

        if (isset($snapshot['items'])) {
            $changes = [...$changes, ...$this->detectItemChanges($snapshot['items'], $order)];
        }

        if (array_key_exists('customer_email', $snapshot) && $snapshot['customer_email'] !== $order->customer_email) {
            $changes[] = [
                'type' => 'contact_email_changed',
                'from' => $snapshot['customer_email'],
                'to' => $order->customer_email,
            ];
        }

        if (array_key_exists('customer_id', $snapshot) && $snapshot['customer_id'] !== $order->customer_id) {
            $changes[] = ['type' => 'customer_changed'];
        }

        if (array_key_exists('shipping_rate_id', $snapshot) && $snapshot['shipping_rate_id'] !== $order->shipping_rate_id) {
            $changes[] = [
                'type' => 'shipping_changed',
                'from' => $snapshot['shipping_rate_name'] ?: null,
                'to' => $order->getTranslations('shipping_rate_name') ?: null,
            ];
        }

        if (array_key_exists('payment_gateway_id', $snapshot) && $snapshot['payment_gateway_id'] !== $order->payment_gateway_id) {
            $changes[] = [
                'type' => 'payment_gateway_changed',
                'from' => $snapshot['payment_gateway_name'] ?: null,
                'to' => $order->getTranslations('payment_gateway_name') ?: null,
            ];
        }

        if (array_key_exists('coupon_code', $snapshot) && $snapshot['coupon_code'] !== $order->coupon_code) {
            $changes[] = [
                'type' => 'coupon_changed',
                'from' => $snapshot['coupon_code'],
                'to' => $order->coupon_code,
            ];
        }

        if ($changes === []) {
            return;
        }

        $this->storeOrderActivityAction->handle(
            order: $order,
            type: OrderActivityType::OrderEdited,
            user: $user,
            metadata: ['changes' => $changes],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $originalItems
     * @return array<int, array<string, mixed>>
     */
    private function detectItemChanges(array $originalItems, Order $order): array
    {
        $changes = [];
        $originalById = collect($originalItems)->keyBy('id');
        $currentById = $order->items->keyBy('id');

        foreach ($currentById as $id => $item) {
            if (! $originalById->has($id)) {
                $changes[] = [
                    'type' => 'item_added',
                    'title' => $item->getTranslations('product_title'),
                    'variant' => $item->variant_title,
                    'quantity' => $item->quantity,
                ];
            } elseif (($original = $originalById[$id]) !== null) {
                if ($original['quantity'] !== $item->quantity) {
                    $changes[] = [
                        'type' => 'item_quantity_changed',
                        'title' => $item->getTranslations('product_title'),
                        'variant' => $item->variant_title,
                        'from' => $original['quantity'],
                        'to' => $item->quantity,
                    ];
                }
            }
        }

        foreach ($originalById as $id => $original) {
            if (! $currentById->has($id)) {
                $changes[] = [
                    'type' => 'item_removed',
                    'title' => $original['title'],
                    'variant' => $original['variant'],
                    'quantity' => $original['quantity'],
                ];
            }
        }

        return $changes;
    }
}
