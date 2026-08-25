<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Address\AddressFieldRules;
use App\DTOs\UpdateOrderInput;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Rules\SellingCountryRule;
use App\Rules\ShippedItemsPreserved;
use App\Rules\ValidProductVariant;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Override;
use Propaganistas\LaravelPhone\Rules\Phone;

final class UpdateOrderRequest extends FormRequest
{
    private ?bool $orderRequiresShipping = null;

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'customer_id' => mb_strtolower(__('Customer')),
            'customer_email' => mb_strtolower(__('Email address')),
            'coupon_code' => mb_strtolower(__('Coupon code')),
            'currency_code' => mb_strtolower(__('Currency')),
            'shipping_rate_id' => mb_strtolower(__('Shipping method')),
            'payment_gateway_id' => mb_strtolower(__('Payment method')),
            'notes' => mb_strtolower(__('Notes')),
            'items' => mb_strtolower(__('Items')),
            'items.*.id' => mb_strtolower(__('Item')),
            'items.*.product_id' => mb_strtolower(__('Product')),
            'items.*.product_variant_id' => mb_strtolower(__('Variant')),
            'items.*.quantity' => mb_strtolower(__('Quantity')),
            'items.*.variant_options' => mb_strtolower(__('Variant options')),
            'shipping_address' => mb_strtolower(__('Shipping address')),
            'shipping_address.first_name' => mb_strtolower(__('First name')),
            'shipping_address.last_name' => mb_strtolower(__('Last name')),
            'shipping_address.address_line_1' => mb_strtolower(__('Street address')),
            'shipping_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'shipping_address.country_code' => mb_strtolower(__('Country')),
            'shipping_address.phone' => mb_strtolower(__('Phone')),
            ...AddressFieldRules::attributes($this->addressCountry('shipping_address'), 'shipping_address'),
            'different_billing_address' => mb_strtolower(__('Different billing address')),
            'billing_address' => mb_strtolower(__('Billing address')),
            'billing_address.first_name' => mb_strtolower(__('First name')),
            'billing_address.last_name' => mb_strtolower(__('Last name')),
            'billing_address.address_line_1' => mb_strtolower(__('Street address')),
            'billing_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'billing_address.country_code' => mb_strtolower(__('Country')),
            'billing_address.phone' => mb_strtolower(__('Phone')),
            ...AddressFieldRules::attributes($this->addressCountry('billing_address'), 'billing_address'),
            'notify_customer' => mb_strtolower(__('Notify customer')),
            'restock' => mb_strtolower(__('Restock items')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('order')] Order $order): array
    {
        $requiresShipping = $this->orderRequiresShipping($order);

        $billArrayReq = $requiresShipping ? ['sometimes', 'required_if:different_billing_address,true'] : ['required'];
        $billFieldReq = $requiresShipping ? ['required_with:billing_address'] : ['required'];
        $billPresence = $requiresShipping ? 'required_with:billing_address' : 'required';

        return [
            'customer_id' => ['sometimes', 'nullable', Rule::exists(User::class, 'id')],
            'customer_email' => ['sometimes', 'required', 'email', 'max:255'],
            'currency_code' => ['sometimes', 'required', 'string', 'size:3'],
            'shipping_rate_id' => ['sometimes', 'nullable', 'integer', Rule::exists(ShippingRate::class, 'id')],
            'payment_gateway_id' => [
                'sometimes', 'nullable', Rule::exists(PaymentGateway::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($order): void {
                    if ((int) $value !== $order->payment_gateway_id
                        && BigDecimal::of($order->paid_total)->isPositive()) {
                        $fail(__('Payment method cannot be changed after payment has been collected.'));
                    }
                },
            ],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'items' => ['sometimes', 'array', 'min:1', new ShippedItemsPreserved($order)],
            'items.*.id' => ['sometimes', 'nullable', Rule::exists(OrderItem::class, 'id')->where('order_id', $order->id)],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.product_variant_id' => Rule::forEach(
                fn (mixed $value, string $attribute, array $data): array => $this->productVariantRules($attribute, $data),
            ),
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.variant_options' => ['sometimes', 'nullable', 'array'],
            'shipping_address' => ['sometimes', 'array'],
            'shipping_address.first_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.last_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.address_line_1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_address.country_code' => ['required_with:shipping_address', 'string', 'size:2', new SellingCountryRule],
            'shipping_address.phone' => ['sometimes', 'nullable', 'string', 'max:20', new Phone],
            ...AddressFieldRules::rules($this->addressCountry('shipping_address'), 'shipping_address', 'required_with:shipping_address'),
            'different_billing_address' => ['sometimes', 'boolean'],
            'billing_address' => [...$billArrayReq, 'array'],
            'billing_address.first_name' => [...$billFieldReq, 'string', 'max:255'],
            'billing_address.last_name' => [...$billFieldReq, 'string', 'max:255'],
            'billing_address.address_line_1' => [...$billFieldReq, 'string', 'max:255'],
            'billing_address.address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.country_code' => [...$billFieldReq, 'string', 'size:2', new SellingCountryRule],
            'billing_address.phone' => ['sometimes', 'nullable', 'string', 'max:20', new Phone],
            ...AddressFieldRules::rules($this->addressCountry('billing_address'), 'billing_address', $billPresence),
            'notify_customer' => ['sometimes', 'boolean'],
            'restock' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): UpdateOrderInput
    {
        return UpdateOrderInput::fromArray($this->validated());
    }

    private function orderRequiresShipping(Order $order): bool
    {
        if ($this->orderRequiresShipping !== null) {
            return $this->orderRequiresShipping;
        }

        if ($this->has('items')) {
            $items = $this->input('items');
            $productIds = is_array($items) ? array_filter(Arr::pluck($items, 'product_id')) : [];

            if ($productIds !== []) {
                return $this->orderRequiresShipping = Product::query()
                    ->whereKey($productIds)
                    ->get(['id', 'type'])
                    ->contains(fn (Product $product): bool => $product->requiresShipping());
            }
        }

        $orderItems = $order->items()->get(['id']);

        return $this->orderRequiresShipping = $orderItems->isEmpty()
            || $orderItems->contains(fn (OrderItem $item): bool => $item->requires_shipping);
    }

    private function addressCountry(string $prefix): string
    {
        $value = $this->input("{$prefix}.country_code");

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     */
    private function productVariantRules(string $attribute, array $data): array
    {
        $segments = explode('.', $attribute);
        $index = $segments[1] ?? null;

        return [
            'sometimes',
            'nullable',
            'uuid',
            new ValidProductVariant(Arr::get($data, "items.{$index}.product_id")),
        ];
    }
}
