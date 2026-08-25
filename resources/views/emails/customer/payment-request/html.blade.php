@extends('emails.layout', ['title' => __('Payment required for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('A balance is due on your order.') }}@endsection

@section('content')
    <h1>{{ __('Payment required') }}</h1>
    <p>{{ __('Your order #:id has been updated and a remaining balance is due.', ['id' => $order->id]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:13px; color:#6b7280;">{{ __('Amount due') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:20px; font-weight:700; color:#0f172a;">{{ \App\Utilities\MoneyFormatter::format($paymentRequest->amount, $paymentRequest->currency_code) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Order') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">#{{ $order->id }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.components.button', ['url' => $payUrl, 'label' => __('Pay now')])
@endsection
