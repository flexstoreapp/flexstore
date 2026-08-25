@extends('emails.layout', ['title' => __('Return requested for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('A customer requested a return for order #:id.', ['id' => $order->id]) }}@endsection

@section('content')
    <h1>{{ __('New return request') }}</h1>
    <p>{{ __('A customer requested a return for order #:id. Review the items below and approve or decline the request.', ['id' => $order->id]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <div style="font-size:13px; color:#6b7280; margin-bottom:8px;">{{ __('Items to return') }}</div>
                @foreach ($return->items as $item)
                    @if ($item->orderItem)
                        <div style="font-size:14px; color:#1f2937; padding-top:4px;">
                            {{ $item->orderItem->getTranslation('product_title', app()->getLocale()) }}@if ($item->orderItem->variant_title) &middot; {{ $item->orderItem->variant_title }}@endif &times; {{ $item->quantity }}
                        </div>
                    @endif
                @endforeach
            </td>
        </tr>
    </table>

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('Review the return')])
    @endif
@endsection
