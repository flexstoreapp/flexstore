@extends('emails.layout', ['title' => __('Order #:id canceled', ['id' => $order->id])])

@section('preheader'){{ __('A customer order has been canceled.') }}@endsection

@section('content')
    <h1>{{ __('Order canceled') }}</h1>
    <p>{{ __('Order #:id has been canceled by :email.', ['id' => $order->id, 'email' => $order->customer_email]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Order') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600;">#{{ $order->id }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Total') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; padding-top:8px;">{{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}</td>
                    </tr>
                    @if ($order->cancellation_note)
                        <tr>
                            <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Reason') }}</td>
                            <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $order->cancellation_note }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View in admin')])
@endsection
