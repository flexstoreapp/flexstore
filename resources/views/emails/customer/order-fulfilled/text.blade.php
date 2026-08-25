{{ __('Your order is on the way') }}

{{ __('Good news — order #:id has shipped and is heading your way.', ['id' => $order->id]) }}

@php
    $loc = $locale ?? app()->getLocale();
    $carrierName = $order->shipping_provider ?? (is_array($order->shipping_carrier_name)
        ? ($order->shipping_carrier_name[$loc] ?? $order->shipping_carrier_name['en'] ?? reset($order->shipping_carrier_name))
        : $order->shipping_carrier_name);
    $rateName = is_array($order->shipping_rate_name)
        ? ($order->shipping_rate_name[$loc] ?? $order->shipping_rate_name['en'] ?? reset($order->shipping_rate_name))
        : $order->shipping_rate_name;
    $shippingDisplay = match (true) {
        $rateName && $carrierName => "{$rateName} ({$carrierName})",
        (bool) $rateName => $rateName,
        default => $carrierName,
    };
@endphp
@if ($shippingDisplay)
{{ __('Shipping') }}: {{ $shippingDisplay }}
@endif
@if ($shipment->tracking_number)
{{ __('Tracking') }}: {{ $shipment->tracking_number }}@if ($shipment->tracking_url) ({{ $shipment->tracking_url }})@endif
@endif
@if ($viewUrl)

{{ __('View your order') }}: {{ $viewUrl }}
@endif
