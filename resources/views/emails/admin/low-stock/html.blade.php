@extends('emails.layout', ['title' => __('Low stock alert: :name', ['name' => $name])])

@section('preheader'){{ __('Inventory is running low — :name', ['name' => $name]) }}@endsection

@section('content')
    <h1>{{ __('Low stock alert') }}</h1>
    <p>{{ __(':name is running low on stock.', ['name' => $name]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Product') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600;">{{ $name }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Current stock') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:18px; color:#b91c1c; font-weight:700; padding-top:8px;">{{ $stock }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View inventory')])
@endsection
