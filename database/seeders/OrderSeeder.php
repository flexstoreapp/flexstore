<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\RecalculateCustomerLifetimeValueAction;
use App\Actions\ReconcileOrderFinancialsAction;
use App\Enums\OrderActivityType;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\TaxCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderTaxDetail;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

final class OrderSeeder extends Seeder
{
    public function __construct(
        private readonly RecalculateCustomerLifetimeValueAction $recalculateCustomerLifetimeValueAction,
        private readonly ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
    ) {
    }

    public function run(): void
    {
        $shippingRates = ShippingRate::with('carrier', 'region')->get();
        $paymentGateways = PaymentGateway::all();
        $customers = User::query()
            ->whereRelation('roles', 'name', Role::Customer)
            ->get();

        $adminUsers = User::query()
            ->whereRelation('roles', 'name', Role::SuperAdmin)
            ->get();

        $coupons = Coupon::query()->where('is_active', true)->get();

        $categories = Category::all();
        $taxCategories = TaxCategory::cases();

        $products = Product::factory(15)->withVariants()->create([
            'category_id' => fn () => $categories->random()->id,
            'tax_category' => fn () => fake()->randomElement($taxCategories),
        ]);

        $this->createUnfulfilledOrders($shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons);
        $this->createInProgressOrders($shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons);
        $this->createFulfilledOrders($shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons);
        $this->createCanceledOrders($shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons);

        $this->recalculateCustomerLifetimeValues($customers);
    }

    private function createUnfulfilledOrders(Collection $shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons): void
    {
        for ($i = 0; $i < 3; $i++) {
            $customer = $customers->random();

            $order = Order::factory()
                ->unfulfilled()
                ->create($this->shippingAttributes($shippingRates) + [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'payment_gateway_id' => $paymentGateways->random()->id,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);

            $this->seedOrderRelations($order, $products, $adminUsers, $coupons);
        }
    }

    private function createInProgressOrders(Collection $shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons): void
    {
        for ($i = 0; $i < 3; $i++) {
            $customer = $customers->random();

            $order = Order::factory()
                ->inProgress()
                ->create($this->shippingAttributes($shippingRates) + [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'payment_gateway_id' => $paymentGateways->random()->id,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);

            $this->seedOrderRelations($order, $products, $adminUsers, $coupons);
        }
    }

    private function createFulfilledOrders(Collection $shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons): void
    {
        for ($i = 0; $i < 3; $i++) {
            $customer = $customers->random();

            $order = Order::factory()
                ->fulfilled()
                ->create($this->shippingAttributes($shippingRates) + [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'payment_gateway_id' => $paymentGateways->random()->id,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);

            $this->seedOrderRelations($order, $products, $adminUsers, $coupons);
        }
    }

    private function createCanceledOrders(Collection $shippingRates, $paymentGateways, $products, $customers, $adminUsers, $coupons): void
    {
        for ($i = 0; $i < 3; $i++) {
            $customer = $customers->random();

            $order = Order::factory()
                ->canceled()
                ->create($this->shippingAttributes($shippingRates) + [
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'payment_gateway_id' => $paymentGateways->random()->id,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);

            $this->seedOrderRelations($order, $products, $adminUsers, $coupons);
        }
    }

    private function shippingAttributes(Collection $shippingRates): array
    {
        $rate = $shippingRates->random();

        return [
            'shipping_carrier_id' => $rate->carrier?->id,
            'shipping_carrier_name' => $rate->carrier?->getTranslations('name') ?? [],
            'shipping_rate_id' => $rate->id,
            'shipping_rate_name' => $rate->getTranslations('name'),
            'region_id' => $rate->region_id,
            'region_name' => $rate->region?->getTranslations('name') ?: null,
        ];
    }

    private function seedOrderRelations(Order $order, $products, $adminUsers, $coupons): void
    {
        $this->createOrderItems($order, $products);
        $this->recalculateOrderTotals($order);
        $this->createOrderAddresses($order);
        $this->createOrderActivities($order, $adminUsers);
        $this->createOrderTaxDetails($order);
        $this->applyCouponToOrder($order, $coupons);
        $this->applyTransactionDetails($order);
        $this->reconcileOrderFinancialsAction->handle($order);
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');

        $subtotal = round((float) $order->items->sum('total_price'), 2);
        $taxTotal = round((float) $order->items->sum('tax_amount'), 2);
        $shippingTotal = (float) $order->shipping_total;
        $total = round($subtotal + $taxTotal + $shippingTotal, 2);

        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => '0.00',
            'total' => $total,
        ]);
    }

    private function createOrderItems(Order $order, $products): void
    {
        $itemCount = fake()->numberBetween(1, 4);
        $selectedProducts = $products->random($itemCount);

        foreach ($selectedProducts as $product) {
            OrderItem::factory()
                ->forOrder($order)
                ->forProduct($product)
                ->create();
        }
    }

    private function createOrderAddresses(Order $order): void
    {
        OrderAddress::factory()
            ->billing()
            ->forOrder($order)
            ->create();

        OrderAddress::factory()
            ->shipping()
            ->forOrder($order)
            ->create();
    }

    private function createOrderActivities(Order $order, $adminUsers): void
    {
        $user = $adminUsers->isNotEmpty() ? $adminUsers->random() : null;

        OrderActivity::factory()
            ->forOrder($order)
            ->create([
                'user_id' => $user?->id,
                'type' => OrderActivityType::OrderPlaced,
            ]);

        OrderActivity::factory()
            ->paymentStatusChange(to: $order->payment_status)
            ->forOrder($order)
            ->create([
                'user_id' => $user?->id,
            ]);
    }

    private function createOrderTaxDetails(Order $order): void
    {
        $order->load('items');

        $generalTaxRates = TaxRate::query()
            ->where('is_active', true)
            ->whereNull('tax_category')
            ->orderBy('priority')
            ->limit(2)
            ->get();

        // Create order-level tax details (general taxes like GST, PST)
        foreach ($generalTaxRates as $taxRate) {
            $taxableAmount = $order->subtotal;
            $taxAmount = round($taxableAmount * ($taxRate->rate / 100), 2);

            OrderTaxDetail::factory()
                ->forOrder($order)
                ->forTaxRate($taxRate)
                ->create([
                    'order_item_id' => null,
                    'taxable_amount' => $taxableAmount,
                    'tax_amount' => $taxAmount,
                ]);
        }

        $categoryTaxRates = TaxRate::query()
            ->where('is_active', true)
            ->whereNotNull('tax_category')
            ->orderBy('priority')
            ->limit(2)
            ->get();

        // Create item-level tax details for specific tax categories
        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }
            if ($product->is_tax_exempt) {
                continue;
            }

            foreach ($categoryTaxRates as $taxRate) {
                $taxableAmount = $item->total_price;
                $taxAmount = round($taxableAmount * ($taxRate->rate / 100), 2);

                OrderTaxDetail::factory()
                    ->forOrderItem($item)
                    ->forTaxRate($taxRate)
                    ->create([
                        'taxable_amount' => $taxableAmount,
                        'tax_amount' => $taxAmount,
                    ]);
            }
        }
    }

    private function applyCouponToOrder(Order $order, $coupons): void
    {
        if ($coupons->isEmpty() || ! fake()->boolean(30)) {
            return;
        }

        $coupon = $coupons->random();

        if ($coupon->min_order_value && $order->subtotal < $coupon->min_order_value) {
            return;
        }

        $discountAmount = 0;
        if ($coupon->type === 'percentage') {
            $discountAmount = round($order->subtotal * ($coupon->value / 100), 2);

            if ($coupon->maximum_discount && $discountAmount > $coupon->maximum_discount) {
                $discountAmount = $coupon->maximum_discount;
            }
        } else {
            $discountAmount = $coupon->value;
        }

        $total = round((float) $order->subtotal + (float) $order->tax_total + (float) $order->shipping_total - $discountAmount, 2);

        $order->update([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount_total' => $discountAmount,
            'total' => max($total, 0),
        ]);
    }

    private function applyTransactionDetails(Order $order): void
    {
        $gateway = $order->paymentGateway;

        if (! $gateway) {
            return;
        }

        $isPaid = in_array($order->payment_status, [
            PaymentStatus::Paid, PaymentStatus::PartiallyPaid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded,
        ], true);

        if ($gateway->driver === PaymentGatewayDriver::Cod) {
            if (! $isPaid) {
                return;
            }

            OrderTransaction::query()->create([
                'order_id' => $order->id,
                'type' => TransactionType::Sale,
                'status' => TransactionStatus::Success,
                'amount' => $order->total,
                'currency_code' => $order->currency_code,
                'payment_gateway_id' => $gateway->id,
            ]);

            return;
        }

        $gatewayReference = match ($gateway->driver) {
            PaymentGatewayDriver::Stripe => 'pi_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            PaymentGatewayDriver::Paypal => fake()->regexify('[A-Z0-9]{17}'),
            PaymentGatewayDriver::Razorpay => 'pay_' . fake()->regexify('[a-zA-Z0-9]{14}'),
            PaymentGatewayDriver::Mollie => 'tr_' . fake()->regexify('[a-zA-Z0-9]{14}'),
            default => null,
        };

        if ($gatewayReference === null) {
            return;
        }

        OrderTransaction::query()->create([
            'order_id' => $order->id,
            'type' => TransactionType::Sale,
            'status' => $isPaid ? TransactionStatus::Success : TransactionStatus::Pending,
            'amount' => $order->total,
            'currency_code' => $order->currency_code,
            'payment_gateway_id' => $gateway->id,
            'gateway_reference' => $gatewayReference,
        ]);
    }

    /**
     * @param  Collection<int, User>  $customers
     */
    private function recalculateCustomerLifetimeValues(Collection $customers): void
    {
        foreach ($customers as $customer) {
            $this->recalculateCustomerLifetimeValueAction->handle($customer);
        }
    }
}
