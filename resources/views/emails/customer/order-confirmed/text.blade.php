{{ __('Order #:id confirmed', ['id' => $order->id]) }}

@if ($order->items->contains(fn ($item) => $item->requires_shipping))
{{ __('Thanks for your order! It has been received and is being processed.') }}
@else
{{ __('Thanks for your order! It has been received and your digital items are ready to download.') }}
@endif

{{ __('Order date') }}: {{ $order->created_at->isoFormat('LL') }}

{{ __('Order summary') }}:
@foreach ($order->items as $item)
- {{ $item->product_title }}@if ($item->variant_title) ({{ $item->variant_title }})@endif x{{ $item->quantity }}  {{ \App\Utilities\MoneyFormatter::format($item->total_price, $order->currency_code) }}
@endforeach

{{ __('Subtotal') }}: {{ \App\Utilities\MoneyFormatter::format($order->subtotal, $order->currency_code) }}
@if ((float) $order->discount_total > 0)
{{ __('Discount') }}: -{{ \App\Utilities\MoneyFormatter::format($order->discount_total, $order->currency_code) }}
@endif
@if ((float) $order->shipping_total > 0)
{{ __('Shipping') }}: {{ \App\Utilities\MoneyFormatter::format($order->shipping_total, $order->currency_code) }}
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
{{ $taxName }} ({{ __(':tax_rate% on :taxable_amount', ['tax_rate' => $taxRate, 'taxable_amount' => \App\Utilities\MoneyFormatter::format($taxDetail->taxable_amount, $order->currency_code)]) }}): {{ \App\Utilities\MoneyFormatter::format($taxDetail->tax_amount, $order->currency_code) }}
@endforeach
@if ($order->taxDetails->count() > 1)
{{ __('Total tax') }}: {{ \App\Utilities\MoneyFormatter::format($order->tax_total, $order->currency_code) }}
@endif
@else
{{ __('Tax') }}: {{ \App\Utilities\MoneyFormatter::format($order->tax_total, $order->currency_code) }}
@endif
@endif
{{ __('Total') }}: {{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}
@if (! empty($downloads ?? []))

{{ __('Downloads') }}:
@foreach ($downloads as $download)
- {{ $download['name'] }}: {{ $download['url'] }}
@endforeach
@endif

@if ($viewUrl){{ __('View your order') }}: {{ $viewUrl }}@endif
@if ($trackUrl){{ __('Track your order') }}: {{ $trackUrl }}@endif
