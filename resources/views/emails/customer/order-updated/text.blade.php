{{ __('Order #:id updated', ['id' => $order->id]) }}

{{ __('Order #:id has new details. Visit the link below to see what changed.', ['id' => $order->id]) }}

{{ __('Total') }}: {{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}

@if ($viewUrl){{ __('View your order') }}: {{ $viewUrl }}@endif
