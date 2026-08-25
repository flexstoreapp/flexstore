@extends('emails.layout', ['title' => __('New customer registration')])

@section('preheader'){{ __('A new customer has signed up.') }}@endsection

@section('content')
    <h1>{{ __('New customer registration') }}</h1>
    <p>{{ __('A new customer has registered.') }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Name') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600;">{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Email address') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#1f2937; padding-top:8px;">{{ $customer->email }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View customer')])
@endsection
