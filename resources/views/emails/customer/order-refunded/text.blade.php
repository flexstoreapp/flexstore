{{ __('Refund issued for order #:id', ['id' => $order->id]) }}

{{ __('We’ve issued a refund of :amount for order #:id. The amount should appear in your account within a few business days.', ['amount' => \App\Utilities\MoneyFormatter::format($refund->amount, $order->currency_code), 'id' => $order->id]) }}

@if ($viewUrl){{ __('View your order') }}: {{ $viewUrl }}@endif
