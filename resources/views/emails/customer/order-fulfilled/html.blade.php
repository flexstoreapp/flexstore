@extends('emails.layout', ['title' => __('Your order is on the way')])

@section('preheader'){{ __('Order #:id has shipped.', ['id' => $order->id]) }}@endsection

@section('json_ld')
{!! json_encode(\App\Utilities\EmailSchemaBuilder::forShipment($order, $shipment), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

@section('content')
    <h1>{{ __('Your order is on the way') }}</h1>
    <p>{{ __('Good news — order #:id has shipped and is heading your way.', ['id' => $order->id]) }}</p>

    @php
        $carrierName = $order->shipping_provider ?? (is_array($order->shipping_carrier_name)
            ? ($order->shipping_carrier_name[$locale] ?? $order->shipping_carrier_name['en'] ?? reset($order->shipping_carrier_name))
            : $order->shipping_carrier_name);
        $rateName = is_array($order->shipping_rate_name)
            ? ($order->shipping_rate_name[$locale] ?? $order->shipping_rate_name['en'] ?? reset($order->shipping_rate_name))
            : $order->shipping_rate_name;
        $shippingDisplay = match (true) {
            $rateName && $carrierName => "{$rateName} ({$carrierName})",
            (bool) $rateName => $rateName,
            default => $carrierName,
        };
    @endphp
    @if ($shipment->tracking_number || $shippingDisplay)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
            <tr>
                <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        @if ($shippingDisplay)
                            <tr>
                                <td style="font-size:12px; color:#6b7280;">{{ __('Shipping') }}</td>
                                <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; font-weight:600; color:#0f172a;">{{ $shippingDisplay }}</td>
                            </tr>
                        @endif
                        @if ($shipment->tracking_number)
                            <tr>
                                <td style="font-size:12px; color:#6b7280;{{ $shippingDisplay ? ' padding-top:8px;' : '' }}">{{ __('Tracking') }}</td>
                                <td dir="ltr" align="{{ $isRtl ? 'left' : 'right' }}" class="es-mono" style="font-size:13px; color:#1f2937; font-family: 'Courier New', Courier, monospace;{{ $shippingDisplay ? ' padding-top:8px;' : '' }}">
                                    @if ($shipment->tracking_url)
                                        <a href="{{ $shipment->tracking_url }}" style="color:{{ $themeColorDark }}; text-decoration: underline;">{{ $shipment->tracking_number }}</a>
                                    @else
                                        {{ $shipment->tracking_number }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif

    @if ($order->shippingAddress)
        <h2>{{ __('Shipping address') }}</h2>
        @include('emails.components.address', ['address' => $order->shippingAddress])
    @endif
@endsection
