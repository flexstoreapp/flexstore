<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Address\AddressFieldRules;
use App\DTOs\AddressLocation;
use App\DTOs\TaxableItem;
use App\DTOs\TaxCalculationInput;
use App\Enums\TaxBasedOn;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class CalculateOrderTaxRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'order_id' => mb_strtolower(__('Order')),
            'items' => mb_strtolower(__('Items')),
            'items.*.product_id' => mb_strtolower(__('Product')),
            'items.*.product_variant_id' => mb_strtolower(__('Variant')),
            'items.*.quantity' => mb_strtolower(__('Quantity')),
            'items.*.unit_price' => mb_strtolower(__('Unit price')),
            'discount_total' => mb_strtolower(__('Discount total')),
            'shipping_total' => mb_strtolower(__('Shipping total')),
            'shipping_address' => mb_strtolower(__('Shipping address')),
            'shipping_address.first_name' => mb_strtolower(__('First name')),
            'shipping_address.last_name' => mb_strtolower(__('Last name')),
            'shipping_address.address_line_1' => mb_strtolower(__('Street address')),
            'shipping_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'shipping_address.city' => mb_strtolower(__('City')),
            'shipping_address.state' => mb_strtolower(__('State')),
            'shipping_address.postal_code' => mb_strtolower(__('Postal code')),
            'shipping_address.country_code' => mb_strtolower(__('Country')),
            'shipping_address.phone' => mb_strtolower(__('Phone')),
            'different_billing_address' => mb_strtolower(__('Different billing address')),
            'billing_address' => mb_strtolower(__('Billing address')),
            'billing_address.first_name' => mb_strtolower(__('First name')),
            'billing_address.last_name' => mb_strtolower(__('Last name')),
            'billing_address.address_line_1' => mb_strtolower(__('Street address')),
            'billing_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'billing_address.city' => mb_strtolower(__('City')),
            'billing_address.state' => mb_strtolower(__('State')),
            'billing_address.postal_code' => mb_strtolower(__('Postal code')),
            'billing_address.country_code' => mb_strtolower(__('Country')),
            'billing_address.phone' => mb_strtolower(__('Phone')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer', Rule::exists(Order::class, 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'items.*.product_variant_id' => ['nullable', 'string', Rule::exists(ProductVariant::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.country_code' => ['required_with:shipping_address', 'string', 'size:2'],
            ...AddressFieldRules::rules($this->addressCountry('shipping_address'), 'shipping_address', 'required_with:shipping_address', ['state', 'postal_code']),
            'different_billing_address' => ['sometimes', 'boolean'],
            'billing_address' => ['required_if:different_billing_address,true', 'nullable', 'array'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
            ...AddressFieldRules::rules($this->addressCountry('billing_address'), 'billing_address', 'required_with:billing_address', ['state', 'postal_code']),
        ];
    }

    public function getCurrencyCode(): string
    {
        $order = $this->getOrder();

        return $order instanceof Order
            ? $order->currency_code
            : (string) Setting::getValue('base_currency');
    }

    public function toDto(): TaxCalculationInput
    {
        $data = $this->validated();
        $itemsData = $data['items'] ?? [];
        $productIds = array_column($itemsData, 'product_id');
        $variantIds = array_filter(array_column($itemsData, 'product_variant_id'));

        $products = Product::query()
            ->select(['id', 'price', 'is_tax_exempt', 'tax_category'])
            ->findMany($productIds)
            ->keyBy('id');

        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->select(['id', 'price'])
                ->findMany($variantIds)
                ->keyBy('id');

        $items = [];
        foreach ($itemsData as $itemData) {
            $product = $products->get($itemData['product_id']);
            if (! $product instanceof Product) {
                continue;
            }

            $variant = isset($itemData['product_variant_id'])
                ? $variants->get($itemData['product_variant_id'])
                : null;

            $unitPrice = $itemData['unit_price']
                ?? $variant->price
                ?? $product->price
                ?? '0.0000';

            $quantity = (int) ($itemData['quantity'] ?? 1);
            $totalPrice = BigDecimal::of($unitPrice)
                ->multipliedBy($quantity)
                ->toScale(4, RoundingMode::HalfUp)
                ->toString();

            $items[] = new TaxableItem(
                id: null,
                totalPrice: $totalPrice,
                isTaxExempt: $product->is_tax_exempt,
                taxCategory: $product->tax_category,
            );
        }

        $subtotal = BigDecimal::zero();
        foreach ($items as $item) {
            $subtotal = $subtotal->plus($item->totalPrice);
        }

        $shippingAddress = isset($data['shipping_address'])
            ? AddressLocation::fromArray($data['shipping_address'])
            : null;

        $billingAddress = ! empty($data['different_billing_address']) && isset($data['billing_address'])
            ? AddressLocation::fromArray($data['billing_address'])
            : $shippingAddress;

        $order = $this->getOrder();

        $storeCountry = $order instanceof Order
            ? $order->tax_store_country_code
            : Setting::getValue('store_country_code');
        $storeAddress = $storeCountry
            ? new AddressLocation(
                countryCode: $storeCountry,
                state: ($order instanceof Order ? $order->tax_store_state : null) ?? Setting::getValue('store_state') ?? '',
                postalCode: ($order instanceof Order ? $order->tax_store_postal_code : null) ?? Setting::getValue('store_postal_code') ?? '',
            )
            : null;

        return new TaxCalculationInput(
            subtotal: $subtotal->toScale(4, RoundingMode::HalfUp)->toString(),
            discountTotal: $data['discount_total'] ?? '0.0000',
            shippingTotal: $data['shipping_total'] ?? '0.0000',
            pricesIncludeTax: $order instanceof Order ? $order->prices_include_tax : (bool) Setting::getValue('prices_include_tax'),
            shippingIsTaxable: $order instanceof Order ? $order->shipping_is_taxable : (bool) Setting::getValue('shipping_is_taxable'),
            taxBasedOn: ($order instanceof Order ? $order->tax_based_on : null) ?? Setting::getValue('tax_based_on') ?? TaxBasedOn::Shipping->value,
            defaultTaxRate: $order instanceof Order ? $order->default_tax_rate : Setting::getValue('default_tax_rate'),
            storeAddress: $storeAddress,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
        );
    }

    private function addressCountry(string $prefix): string
    {
        $value = $this->input("{$prefix}.country_code");

        return is_string($value) ? $value : '';
    }

    private function getOrder(): ?Order
    {
        return once(function (): ?Order {
            $orderId = $this->validated('order_id');

            if ($orderId === null) {
                return null;
            }

            return Order::query()
                ->whereKey($orderId)
                ->select([
                    'id', 'currency_code', 'prices_include_tax', 'shipping_is_taxable',
                    'tax_based_on', 'default_tax_rate',
                    'tax_store_country_code', 'tax_store_state', 'tax_store_postal_code',
                ])
                ->first();
        });
    }
}
