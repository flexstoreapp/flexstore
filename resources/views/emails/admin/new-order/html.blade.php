@extends('emails.layout', ['title' => __('New order #:id', ['id' => $order->id])])

@section('preheader'){{ __('A new order has been placed.') }}@endsection

@section('content')
    <h1>{{ __('New order received') }}</h1>
    <p>{{ __('A new order has been placed by :email.', ['email' => $order->customer_email]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Order') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; font-weight:600; color:#0f172a;">#{{ $order->id }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Total') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; font-weight:600; color:#0f172a; padding-top:8px;">{{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Customer') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $order->customer_email }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View in admin')])

    <h2>{{ __('Order summary') }}</h2>
    @include('emails.components.order-items', ['order' => $order])
    @include('emails.components.order-totals', ['order' => $order])
@endsection
