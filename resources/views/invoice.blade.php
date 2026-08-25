<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #1f2937;
            background: #fff;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 25px 40px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .store-name {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .invoice-number {
            font-size: 14px;
            color: #6b7280;
        }

        .invoice-date {
            font-size: 12px;
            color: #6b7280;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .info-box:last-child {
            padding-right: 0;
        }

        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-content {
            font-size: 12px;
            color: #374151;
        }

        .info-content strong {
            display: block;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .items-table th {
            background-color: #f9fafb;
            padding: 8px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .items-table td.text-right {
            text-align: right;
        }

        .items-table td.text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 600;
        }

        .product-variant {
            font-size: 11px;
            color: #6b7280;
            margin-top: 1px;
        }

        .product-sku {
            font-size: 10px;
            color: #6b7280;
            font-family: monospace;
            margin-top: 2px;
        }

        .summary-section {
            display: table;
            width: 100%;
        }

        .summary-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .summary-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .summary-table {
            width: 100%;
            margin-left: auto;
        }

        .summary-row {
            display: table;
            width: 100%;
        }

        .summary-label {
            display: table-cell;
            padding: 5px 12px;
            text-align: right;
            font-size: 12px;
        }

        .summary-label:not(:last-child) {
            color: #6b7280;
        }

        .summary-value {
            display: table-cell;
            padding: 5px 12px;
            text-align: right;
            font-size: 12px;
            width: 120px;
        }

        .summary-total {
            border-top: 2px solid #e5e7eb;
            margin-top: 8px;
            padding-top: 8px;
        }

        .summary-total .summary-label,
        .summary-total .summary-value {
            font-weight: bold;
            font-size: 14px;
        }

        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 6px;
        }

        .notes-title {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6px;
        }

        .notes-content {
            font-size: 11px;
            color: #6b7280;
            white-space: pre-wrap;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }


        .header-meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 8px;
            line-height: 1.6;
        }

        .header-meta strong {
            color: #374151;
            font-weight: 600;
        }

        .summary-balance-due .summary-label,
        .summary-balance-due .summary-value {
            color: #b45309;
            font-weight: 600;
        }

        .summary-credit-due .summary-label,
        .summary-credit-due .summary-value {
            color: #b91c1c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="header-left">
                @if (($store['logo'] ?? null) && $store['logo']->path !== null && Storage::disk($store['logo']->disk)->exists($store['logo']->path))
                    @php
                        $logoPath = Storage::disk($store['logo']->disk)->path($store['logo']->path);
                        $logoMime = mime_content_type($logoPath) ?: 'image/png';
                        $logoData = base64_encode(file_get_contents($logoPath));
                    @endphp
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="{{ $store['name'] }}" style="max-height: 40px; width: auto; margin-bottom: 4px;">
                @else
                    <div class="store-name">{{ $store['name'] }}</div>
                @endif
                <div style="font-size: 11px; color: #6b7280; line-height: 1.4;">
                    @if ($store['street_address'])
                        {{ $store['street_address'] }}<br>
                    @endif
                    @if ($store['city'] || $store['state'] || $store['postal_code'])
                        {{ implode(', ', array_filter([$store['city'], $store['state']])) }} {{ $store['postal_code'] }}<br>
                    @endif
                    @if ($store['country'])
                        {{ \App\Enums\Country::fromNameOrValue($store['country'])->value }}<br>
                    @endif
                    @if ($store['email'])
                        {{ $store['email'] }}<br>
                    @endif
                    @if ($store['phone'])
                        <span dir="ltr">{{ $store['phone'] }}</span>
                    @endif
                </div>
            </div>

            <div class="header-right">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-number">#{{ $order->id }}</div>
                <div class="invoice-date">{{ $order->created_at->format('F j, Y') }}</div>

                <div class="header-meta">
                    @if ($order->payment_gateway_name)
                        <div><b>Payment:</b> {{ $order->payment_gateway_name }}</div>
                    @endif
                    @if (!empty($order->shipping_rate_name) || !empty($order->shipping_carrier_name))
                        <div>
                            <b>Shipping:</b>
                            @if (!empty($order->shipping_rate_name) && !empty($order->shipping_carrier_name))
                                {{ $order->shipping_rate_name }} ({{ $order->shipping_carrier_label }})
                            @elseif (!empty($order->shipping_rate_name))
                                {{ $order->shipping_rate_name }}
                            @else
                                {{ $order->shipping_carrier_label }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="info-section">
            @if ($order->billingAddress)
                <div class="info-box">
                    <div class="info-label">Billing Address</div>
                    <div class="info-content">
                        <p><b>{{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}</b></p>
                        {{ $order->billingAddress->address_line_1 }}<br>
                        @if ($order->billingAddress->address_line_2)
                            {{ $order->billingAddress->address_line_2 }}<br>
                        @endif
                        {{ $order->billingAddress->city }}, {{ $order->billingAddress->state_name ?? $order->billingAddress->state }} {{ $order->billingAddress->postal_code }}<br>
                        @if ($order->billingAddress->country_code)
                            {{ \App\Enums\Country::fromNameOrValue($order->billingAddress->country_code)->value }}
                        @endif
                        @if ($order->billingAddress->phone)
                            <br><span dir="ltr">{{ $order->billingAddress->phone }}</span>
                        @endif
                    </div>
                </div>
            @endif

            @if ($order->shippingAddress)
                <div class="info-box">
                    <div class="info-label">Shipping Address</div>
                    <div class="info-content">
                        <p><b>{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</b></p>
                        {{ $order->shippingAddress->address_line_1 }}<br>
                        @if ($order->shippingAddress->address_line_2)
                            {{ $order->shippingAddress->address_line_2 }}<br>
                        @endif
                        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state_name ?? $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                        @if ($order->shippingAddress->country_code)
                            {{ \App\Enums\Country::fromNameOrValue($order->shippingAddress->country_code)->value }}
                        @endif
                        @if ($order->shippingAddress->phone)
                            <br><span dir="ltr">{{ $order->shippingAddress->phone }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%">Product</th>
                    <th class="text-center" style="width: 15%">Quantity</th>
                    <th class="text-right" style="width: 17.5%">Unit Price</th>
                    <th class="text-right" style="width: 17.5%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <div class="product-name">
                                {{ $item->product_title }}
                            </div>
                            @if ($item->variant_title)
                                <div class="product-variant">{{ $item->variant_title }}</div>
                            @endif
                            @if ($item->product_sku)
                                <div class="product-sku">SKU: {{ $item->product_sku }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ \App\Utilities\MoneyFormatter::format($item->unit_price, $order->currency_code) }}</td>
                        <td class="text-right">{{ \App\Utilities\MoneyFormatter::format($item->total_price, $order->currency_code) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-left">
                @if ($order->notes)
                    <div style="margin-top: 5px;">
                        <div style="font-size: 12px; font-weight: bold; color: #374151; margin-bottom: 3px;">Order notes</div>
                        <div style="font-size: 11px; color: #6b7280; white-space: pre-wrap;">{{ $order->notes }}</div>
                    </div>
                @endif
            </div>

            <div class="summary-right">
                <table class="summary-table">
                    <tr class="summary-row">
                        <td class="summary-label">Subtotal{{ $order->prices_include_tax ? ' (incl. tax)' : '' }}</td>
                        <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->subtotal, $order->currency_code) }}</td>
                    </tr>
                    @if ($order->discount_total > 0)
                        <tr class="summary-row">
                            <td class="summary-label">

                                <div style="line-height: 1.3;">
                                    <div>Discount</div>
                                    @if ($order->coupon_code)
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                                            Coupon: {{ $order->coupon_code }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="summary-value">-{{ \App\Utilities\MoneyFormatter::format($order->discount_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                    @if ($order->shipping_total > 0)
                        <tr class="summary-row">
                            <td class="summary-label">Shipping</td>
                            <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->shipping_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                    @if ($order->tax_total > 0)
                        @if ($displayTaxTotals === \App\Enums\DisplayTaxTotals::Itemized && $order->taxDetails->isNotEmpty())
                            @foreach ($order->taxDetails as $taxDetail)
                                <tr class="summary-row">
                                    <td class="summary-label">
                                        <div style="line-height: 1.3;">
                                            <div>{{ is_array($taxDetail->tax_name) ? ($taxDetail->tax_name[app()->getLocale()] ?? $taxDetail->tax_name['en'] ?? reset($taxDetail->tax_name)) : $taxDetail->tax_name }}</div>
                                            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                                                {{ rtrim(number_format((float) $taxDetail->tax_rate, 2), '0.') }}% on {{ \App\Utilities\MoneyFormatter::format($taxDetail->taxable_amount, $order->currency_code) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($taxDetail->tax_amount, $order->currency_code) }}</td>
                                </tr>
                            @endforeach
                            @if ($order->taxDetails->count() > 1)
                                <tr class="summary-row">
                                    <td class="summary-label">Total tax</td>
                                    <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->tax_total, $order->currency_code) }}</td>
                                </tr>
                            @endif
                        @else
                            <tr class="summary-row">
                                <td class="summary-label">Tax</td>
                                <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->tax_total, $order->currency_code) }}</td>
                            </tr>
                        @endif
                    @endif
                    <tr class="summary-row summary-total">
                        <td class="summary-label">Total</td>
                        <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}</td>
                    </tr>
                    @php
                        $hasRefunds = $order->refund_total > 0;
                        $hasBalanceDue = $order->balance_due_total > 0;
                        $hasCreditDue = $order->credit_due_total > 0;
                        $showPaidBreakdown = $hasRefunds || $hasBalanceDue || $hasCreditDue;
                    @endphp
                    @if ($showPaidBreakdown && $order->paid_total > 0)
                        <tr class="summary-row">
                            <td class="summary-label">Paid</td>
                            <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->paid_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                    @if ($hasRefunds)
                        <tr class="summary-row">
                            <td class="summary-label">Refunded</td>
                            <td class="summary-value">-{{ \App\Utilities\MoneyFormatter::format($order->refund_total, $order->currency_code) }}</td>
                        </tr>
                        <tr class="summary-row summary-total">
                            <td class="summary-label">Net payment</td>
                            <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->net_paid_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                    @if ($hasBalanceDue)
                        <tr class="summary-row summary-balance-due">
                            <td class="summary-label">Balance due</td>
                            <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->balance_due_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                    @if ($hasCreditDue)
                        <tr class="summary-row summary-credit-due">
                            <td class="summary-label">Credit owed</td>
                            <td class="summary-value">{{ \App\Utilities\MoneyFormatter::format($order->credit_due_total, $order->currency_code) }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
        <div class="footer">Thank you for your business!</div>
    </div>
</body>
</html>
