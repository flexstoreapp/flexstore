@php
    $fmt = fn ($v) => \App\Utilities\MoneyFormatter::format($v, $order->currency_code);
    $rtl = $isRtl ?? false;
    $labelAlign = $rtl ? 'right' : 'left';
    $valueAlign = $rtl ? 'left' : 'right';
    $rowGap = 'padding-top:6px;';
    $labelStyle = 'font-size:13px; color:#6b7280;';
    $valueStyle = 'font-size:13px; color:#1f2937;';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0 8px;">
    <tr>
        <td align="{{ $labelAlign }}" style="{{ $labelStyle }}">{{ __('Subtotal') }}</td>
        <td align="{{ $valueAlign }}" style="{{ $valueStyle }}">{{ $fmt($order->subtotal) }}</td>
    </tr>
    @if ((float) $order->discount_total > 0)
        <tr>
            <td align="{{ $labelAlign }}" style="{{ $labelStyle }} {{ $rowGap }}">
                {{ __('Discount') }}@if ($order->coupon_code) <span style="color:#6b7280; font-size:11px;">({{ $order->coupon_code }})</span>@endif
            </td>
            <td align="{{ $valueAlign }}" style="{{ $valueStyle }} {{ $rowGap }}"><span dir="ltr">-{{ $fmt($order->discount_total) }}</span></td>
        </tr>
    @endif
    @if ((float) $order->shipping_total > 0)
        <tr>
            <td align="{{ $labelAlign }}" style="{{ $labelStyle }} {{ $rowGap }}">{{ __('Shipping') }}</td>
            <td align="{{ $valueAlign }}" style="{{ $valueStyle }} {{ $rowGap }}">{{ $fmt($order->shipping_total) }}</td>
        </tr>
    @endif
    @if ((float) $order->tax_total > 0)
        @if ($displayTaxTotals === \App\Enums\DisplayTaxTotals::Itemized && $order->taxDetails->isNotEmpty())
            @foreach ($order->taxDetails as $taxDetail)
                @php
                    $taxName = is_array($taxDetail->tax_name)
                        ? ($taxDetail->tax_name[$locale] ?? $taxDetail->tax_name['en'] ?? reset($taxDetail->tax_name))
                        : $taxDetail->tax_name;
                    $taxRate = rtrim(rtrim(number_format((float) $taxDetail->tax_rate, 2, '.', ''), '0'), '.');
                @endphp
                <tr>
                    <td align="{{ $labelAlign }}" style="{{ $labelStyle }} {{ $rowGap }}">
                        {{ $taxName }}
                        <span style="color:#6b7280; font-size:11px;">({{ __(':tax_rate% on :taxable_amount', [
                            'tax_rate' => $taxRate,
                            'taxable_amount' => $fmt($taxDetail->taxable_amount),
                        ]) }})</span>
                    </td>
                    <td align="{{ $valueAlign }}" style="{{ $valueStyle }} {{ $rowGap }}">{{ $fmt($taxDetail->tax_amount) }}</td>
                </tr>
            @endforeach
            @if ($order->taxDetails->count() > 1)
                <tr>
                    <td align="{{ $labelAlign }}" style="{{ $labelStyle }} {{ $rowGap }}">{{ __('Total tax') }}</td>
                    <td align="{{ $valueAlign }}" style="{{ $valueStyle }} {{ $rowGap }}">{{ $fmt($order->tax_total) }}</td>
                </tr>
            @endif
        @else
            <tr>
                <td align="{{ $labelAlign }}" style="{{ $labelStyle }} {{ $rowGap }}">{{ __('Tax') }}</td>
                <td align="{{ $valueAlign }}" style="{{ $valueStyle }} {{ $rowGap }}">{{ $fmt($order->tax_total) }}</td>
            </tr>
        @endif
    @endif
    <tr><td colspan="2" style="height:12px; line-height:12px; font-size:1px;">&nbsp;</td></tr>
    <tr>
        <td align="{{ $labelAlign }}" style="border-top:1px solid #e5e7eb; padding-top:12px; font-size:13px; font-weight:600; color:#0f172a;">{{ __('Total') }}</td>
        <td align="{{ $valueAlign }}" style="border-top:1px solid #e5e7eb; padding-top:12px; font-size:13px; font-weight:600; color:#0f172a;">{{ $fmt($order->total) }}</td>
    </tr>
    @if ((float) $order->paid_total > 0)
        <tr>
            <td align="{{ $labelAlign }}" style="padding-top:6px; font-size:12px; color:#6b7280;">{{ __('Paid') }}</td>
            <td align="{{ $valueAlign }}" style="padding-top:6px; font-size:12px; color:#1f2937;">{{ $fmt($order->paid_total) }}</td>
        </tr>
    @endif
    @if ((float) $order->refund_total > 0)
        <tr>
            <td align="{{ $labelAlign }}" style="padding-top:6px; font-size:12px; color:#6b7280;">{{ __('Refunded') }}</td>
            <td align="{{ $valueAlign }}" style="padding-top:6px; font-size:12px; color:#1f2937;"><span dir="ltr">-{{ $fmt($order->refund_total) }}</span></td>
        </tr>
    @endif
    @if ((float) $order->balance_due_total > 0)
        <tr>
            <td align="{{ $labelAlign }}" style="padding-top:6px; font-size:13px; font-weight:600; color:#b91c1c;">{{ __('Balance due') }}</td>
            <td align="{{ $valueAlign }}" style="padding-top:6px; font-size:13px; font-weight:600; color:#b91c1c;">{{ $fmt($order->balance_due_total) }}</td>
        </tr>
    @endif
</table>
