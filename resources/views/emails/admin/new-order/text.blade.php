{{ __('New order #:id', ['id' => $order->id]) }}

{{ __('A new order has been placed by :email.', ['email' => $order->customer_email]) }}

{{ __('Total') }}: {{ \App\Utilities\MoneyFormatter::format($order->total, $order->currency_code) }}

{{ __('View in admin') }}: {{ $viewUrl }}
