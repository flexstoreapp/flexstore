@extends('emails.layout', ['title' => __('Order #:id confirmed', ['id' => $order->id])])

@section('preheader'){{ __('Thanks for your order — here’s your receipt.') }}@endsection

@section('json_ld')
{!! json_encode(\App\Utilities\EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderProcessing', $viewUrl), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

@section('content')
    @php
        $paymentName = is_array($order->payment_gateway_name)
            ? ($order->payment_gateway_name[$locale] ?? $order->payment_gateway_name['en'] ?? reset($order->payment_gateway_name))
            : $order->payment_gateway_name;
        $shippingRateName = is_array($order->shipping_rate_name)
            ? ($order->shipping_rate_name[$locale] ?? $order->shipping_rate_name['en'] ?? reset($order->shipping_rate_name))
            : $order->shipping_rate_name;
        $shippingCarrierName = $order->shipping_provider ?? (is_array($order->shipping_carrier_name)
            ? ($order->shipping_carrier_name[$locale] ?? $order->shipping_carrier_name['en'] ?? reset($order->shipping_carrier_name))
            : $order->shipping_carrier_name);
        $shippingName = match (true) {
            $shippingRateName && $shippingCarrierName => "{$shippingRateName} ({$shippingCarrierName})",
            (bool) $shippingRateName => $shippingRateName,
            default => $shippingCarrierName,
        };
        $requiresShipping = $order->items->contains(fn ($item) => $item->requires_shipping);
        $greetingName = $order->shippingAddress?->first_name ?? $order->billingAddress?->first_name ?? $order->customer?->name ?? '';
    @endphp

    <h1>{{ __('Thanks for your order!') }}</h1>
    @if ($requiresShipping)
        <p>{{ __('Hi :name, your order has been received and is now being processed.', ['name' => $greetingName]) }}</p>
    @else
        <p>{{ __('Hi :name, your order has been received and your digital items are ready to download.', ['name' => $greetingName]) }}</p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Order number') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; font-weight:600; color:#0f172a;">#{{ $order->id }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Order date') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $order->created_at->isoFormat('LL') }}</td>
                    </tr>
                    @if ($paymentName)
                        <tr>
                            <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Payment') }}</td>
                            <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $paymentName }}</td>
                        </tr>
                    @endif
                    @if ($shippingName)
                        <tr>
                            <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Shipping') }}</td>
                            <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $shippingName }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @elseif ($trackUrl)
        @include('emails.components.button', ['url' => $trackUrl, 'label' => __('Track your order')])
    @endif

    <h2>{{ __('Order summary') }}</h2>
    @include('emails.components.order-items', ['order' => $order])
    @include('emails.components.order-totals', ['order' => $order])

    @if (! empty($downloads ?? []))
        <h2>{{ __('Downloads') }}</h2>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 8px 0 16px;">
            @foreach ($downloads as $download)
                <tr>
                    <td style="padding:10px 0;@if (! $loop->last) border-bottom:1px solid #e5e7eb;@endif">
                        <a href="{{ $download['url'] }}" target="_blank" style="font-size:14px; font-weight:600; color:#2563eb; text-decoration:none;">{{ $download['name'] }}</a>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($order->shippingAddress)
        <h2>{{ __('Shipping address') }}</h2>
        @include('emails.components.address', ['address' => $order->shippingAddress])
    @endif

    @if ($order->billingAddress)
        <h2>{{ __('Billing address') }}</h2>
        @include('emails.components.address', ['address' => $order->billingAddress])
    @endif
@endsection
