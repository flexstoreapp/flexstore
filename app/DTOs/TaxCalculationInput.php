<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaxBasedOn;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;

final readonly class TaxCalculationInput
{
    /**
     * @param  list<TaxableItem>  $items
     */
    public function __construct(
        public string $subtotal,
        public string $discountTotal,
        public string $shippingTotal,
        public bool $pricesIncludeTax,
        public bool $shippingIsTaxable,
        public string $taxBasedOn,
        public ?string $defaultTaxRate,
        public ?AddressLocation $storeAddress,
        public array $items,
        public ?AddressLocation $shippingAddress = null,
        public ?AddressLocation $billingAddress = null,
    ) {
    }

    public static function fromOrder(Order $order): self
    {
        $order->loadMissing(['items.product', 'shippingAddress', 'billingAddress']);

        $items = array_values($order->items->map(fn (OrderItem $item): TaxableItem => new TaxableItem(
            id: $item->id,
            totalPrice: $item->total_price,
            isTaxExempt: $item->product->is_tax_exempt ?? false,
            taxCategory: $item->product->tax_category ?? null,
        ))->all());

        $shippingAddress = $order->shippingAddress
            ? new AddressLocation(
                countryCode: $order->shippingAddress->country_code,
                state: $order->shippingAddress->state ?? '',
                postalCode: $order->shippingAddress->postal_code ?? '',
            )
            : null;

        $billingAddress = $order->billingAddress
            ? new AddressLocation(
                countryCode: $order->billingAddress->country_code,
                state: $order->billingAddress->state ?? '',
                postalCode: $order->billingAddress->postal_code ?? '',
            )
            : null;

        $storeAddress = $order->tax_store_country_code
            ? new AddressLocation(
                countryCode: $order->tax_store_country_code,
                state: $order->tax_store_state ?? '',
                postalCode: $order->tax_store_postal_code ?? '',
            )
            : null;

        return new self(
            subtotal: $order->subtotal,
            discountTotal: $order->discount_total,
            shippingTotal: $order->shipping_total,
            pricesIncludeTax: $order->prices_include_tax,
            shippingIsTaxable: $order->shipping_is_taxable,
            taxBasedOn: $order->tax_based_on,
            defaultTaxRate: $order->default_tax_rate,
            storeAddress: $storeAddress,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
        );
    }

    /**
     * @param  list<HydratedOrderItem>  $hydratedItems
     */
    public static function fromHydratedItems(
        array $hydratedItems,
        string $subtotal,
        string $shippingTotal,
        string $discountTotal,
        ?AddressLocation $shippingAddress = null,
        ?AddressLocation $billingAddress = null,
    ): self {
        $items = array_map(
            fn (HydratedOrderItem $item): TaxableItem => new TaxableItem(
                id: null,
                totalPrice: $item->totalPrice,
                isTaxExempt: $item->product->is_tax_exempt,
                taxCategory: $item->product->tax_category,
            ),
            $hydratedItems,
        );

        $storeCountry = Setting::getValue('store_country_code');
        $storeState = Setting::getValue('store_state');
        $storePostalCode = Setting::getValue('store_postal_code');

        $storeAddress = $storeCountry
            ? new AddressLocation(
                countryCode: $storeCountry,
                state: $storeState ?? '',
                postalCode: $storePostalCode ?? '',
            )
            : null;

        return new self(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            shippingTotal: $shippingTotal,
            pricesIncludeTax: (bool) Setting::getValue('prices_include_tax'),
            shippingIsTaxable: (bool) Setting::getValue('shipping_is_taxable'),
            taxBasedOn: Setting::getValue('tax_based_on') ?? TaxBasedOn::Shipping->value,
            defaultTaxRate: Setting::getValue('default_tax_rate'),
            storeAddress: $storeAddress,
            items: $items,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
        );
    }
}
