<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0;">
    @foreach ($order->items as $item)
        <tr>
            <td valign="top" style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td valign="top">
                            <div style="font-size:13px; font-weight:600; color:#0f172a; line-height:1.4;">{{ $item->product_title }}</div>
                            @if ($item->variant_title)
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $item->variant_title }}</div>
                            @endif
                            @if ($item->product_sku)
                                <div style="font-size:11px; color:#6b7280; margin-top:2px;"><span dir="ltr">{{ __('SKU') }}: {{ $item->product_sku }}</span></div>
                            @endif
                            <div style="font-size:12px; color:#6b7280; margin-top:4px;">{{ __('Qty') }}: {{ $item->quantity }}</div>
                        </td>
                        <td valign="top" align="{{ ($isRtl ?? false) ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600; white-space:nowrap; padding-{{ ($isRtl ?? false) ? 'right' : 'left' }}: 12px;">
                            {{ \App\Utilities\MoneyFormatter::format($item->total_price, $order->currency_code) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endforeach
</table>
