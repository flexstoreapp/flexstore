@extends('emails.layout', ['title' => __('Order #:id canceled', ['id' => $order->id])])

@section('preheader'){{ __('Your order has been canceled') }}.@endsection

@section('json_ld')
{!! json_encode(\App\Utilities\EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderCancelled', $viewUrl), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

@section('content')
    <h1>{{ __('Your order has been canceled') }}</h1>
    <p>{{ __('Order #:id has been canceled. If a payment was made, the refund will be processed separately.', ['id' => $order->id]) }}</p>

    @if ($order->cancellation_note)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
            <tr>
                <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                    <div style="font-size:11px; text-transform:uppercase; color:#6b7280; font-weight:600; margin-bottom:6px;">{{ __('Reason') }}</div>
                    <div style="font-size:13px; color:#1f2937; white-space:pre-wrap;">{{ $order->cancellation_note }}</div>
                </td>
            </tr>
        </table>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif

    <h2>{{ __('Order summary') }}</h2>
    @include('emails.components.order-items', ['order' => $order])
    @include('emails.components.order-totals', ['order' => $order])
@endsection
