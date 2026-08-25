@extends('emails.layout', ['title' => __('Complete your order at :store', ['store' => $storeName])])

@section('preheader'){{ __('Your cart is still waiting for you.') }}@endsection

@section('content')
    <h1>{{ $heading }}</h1>
    <p>{!! nl2br(e($body), false) !!}</p>

    @php $items = is_array($session->items) ? $session->items : []; @endphp
    @if (! empty($items))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 8px 0 12px;">
            @foreach ($items as $item)
                @php
                    $title = is_array($item['product_title'] ?? null)
                        ? ($item['product_title'][$locale] ?? $item['product_title']['en'] ?? reset($item['product_title']))
                        : ($item['product_title'] ?? '');
                @endphp
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td valign="top">
                                    <div style="font-size:13px; font-weight:600; color:#0f172a;">{{ $title }}</div>
                                    @if (! empty($item['variant_title']))
                                        <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $item['variant_title'] }}</div>
                                    @endif
                                    <div style="font-size:12px; color:#6b7280; margin-top:4px;">{{ __('Qty') }}: {{ $item['quantity'] ?? 1 }}</div>
                                </td>
                                <td valign="top" align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600; white-space:nowrap;">
                                    {{ \App\Utilities\MoneyFormatter::format($item['total_price'] ?? 0, $session->currency_code ?? 'USD') }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endforeach
        </table>

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 8px 0 16px;">
            @if ((float) $session->discount_total > 0)
                <tr>
                    <td style="font-size:13px; color:#6b7280; padding-bottom:6px;">
                        {{ __('Discount') }}@if ($session->coupon_code) <span style="font-size:11px;">({{ $session->coupon_code }})</span>@endif
                    </td>
                    <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#6b7280; padding-bottom:6px;"><span dir="ltr">-{{ \App\Utilities\MoneyFormatter::format($session->discount_total, $session->currency_code ?? 'USD') }}</span></td>
                </tr>
            @endif
            <tr>
                <td style="font-size:13px; color:#0f172a; font-weight:600;">{{ __('Cart total') }}</td>
                <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600;">{{ \App\Utilities\MoneyFormatter::format($session->total, $session->currency_code ?? 'USD') }}</td>
            </tr>
        </table>
    @endif

    @if ($discountOffer)
        <p style="margin: 0 0 16px; padding: 12px 14px; background-color: #ecfdf5; border-radius: 8px; color: #065f46; font-weight: 600;">{!! nl2br(e($discountOffer), false) !!}</p>
    @endif

    @include('emails.components.button', ['url' => $recoveryUrl, 'label' => __('Complete your order')])

    <p class="es-muted" style="margin-top: 12px;">{{ __('This link will restore your cart so you can pick up where you left off.') }}</p>
@endsection
