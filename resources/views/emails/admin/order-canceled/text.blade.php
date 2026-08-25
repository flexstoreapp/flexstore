{{ __('Order #:id canceled', ['id' => $order->id]) }}

{{ __('Order #:id has been canceled by :email.', ['id' => $order->id, 'email' => $order->customer_email]) }}

{{ __('Total') }}: {{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}
@if ($order->cancellation_note)
{{ __('Reason') }}: {{ $order->cancellation_note }}
@endif

{{ __('View in admin') }}: {{ $viewUrl }}
