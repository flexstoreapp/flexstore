@extends('emails.layout', ['title' => __('Update on your return for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('An update on your return request.') }}@endsection

@section('content')
    <h1>{{ __('Update on your return request') }}</h1>
    <p>{{ __('Unfortunately, your return request for order #:id could not be approved.', ['id' => $order->id]) }}</p>

    @if ($return->decline_reason)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
            <tr>
                <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                    <div style="font-size:13px; color:#6b7280; margin-bottom:4px;">{{ __('Reason') }}</div>
                    <div style="font-size:14px; color:#1f2937;">{{ $return->decline_reason }}</div>
                </td>
            </tr>
        </table>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your return')])
    @endif
@endsection
