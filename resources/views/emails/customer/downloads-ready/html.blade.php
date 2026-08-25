@extends('emails.layout', ['title' => __('Your downloads for order #:id are ready', ['id' => $order->id])])

@section('preheader'){{ __('Your digital items are ready to download.') }}@endsection

@section('content')
    <h1>{{ __('Your downloads are ready') }}</h1>
    <p>{{ __('Payment for order #:id has been received. Your digital items are ready to download.', ['id' => $order->id]) }}</p>

    @if (! empty($downloads ?? []))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 8px 0 16px;">
            @foreach ($downloads as $download)
                <tr>
                    <td style="padding:10px 0;@if (! $loop->last) border-bottom:1px solid #e5e7eb;@endif">
                        <a href="{{ $download['url'] }}" target="_blank" style="font-size:14px; font-weight:600; color:#2563eb; text-decoration:none;">{{ $download['name'] }}</a>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif
@endsection
