@extends('emails.layout', ['title' => __('Tracking updated for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('We’ve updated the tracking info for your shipment.') }}@endsection

@section('json_ld')
{!! json_encode(\App\Utilities\EmailSchemaBuilder::forShipment($order, $shipment), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

@section('content')
    <h1>{{ __('Tracking updated') }}</h1>
    <p>{{ __('We’ve updated the tracking information for order #:id.', ['id' => $order->id]) }}</p>

    @if ($shipment->tracking_number)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
            <tr>
                <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                            <td style="font-size:12px; color:#6b7280;">{{ __('Tracking') }}</td>
                            <td dir="ltr" align="{{ $isRtl ? 'left' : 'right' }}" class="es-mono" style="font-size:13px; color:#1f2937; font-family: 'Courier New', Courier, monospace;">
                                @if ($shipment->tracking_url)
                                    <a href="{{ $shipment->tracking_url }}" style="color:{{ $themeColorDark }}; text-decoration: underline;">{{ $shipment->tracking_number }}</a>
                                @else
                                    {{ $shipment->tracking_number }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif
@endsection
