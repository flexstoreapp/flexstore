@extends('emails.layout', ['title' => __('Refund issued for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('Your refund of :amount has been issued.', ['amount' => \App\Utilities\MoneyFormatter::format($refund->amount, $order->currency_code)]) }}@endsection

@section('json_ld')
{!! json_encode(\App\Utilities\EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderReturned', $viewUrl), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
@endsection

@section('content')
    <h1>{{ __('Your refund has been issued') }}</h1>
    <p>{{ __('We’ve issued a refund for order #:id. The amount should appear in your account within a few business days, depending on your payment provider.', ['id' => $order->id]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:13px; color:#6b7280;">{{ __('Refunded amount') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:18px; font-weight:700; color:#0f172a;">{{ \App\Utilities\MoneyFormatter::format($refund->amount, $order->currency_code) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Order') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">#{{ $order->id }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif
@endsection
