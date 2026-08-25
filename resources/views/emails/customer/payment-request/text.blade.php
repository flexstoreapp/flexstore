{{ __('Payment required for order #:id', ['id' => $order->id]) }}

{{ __('Your order #:id has been updated and a remaining balance is due.', ['id' => $order->id]) }}

{{ __('Amount due') }}: {{ \App\Utilities\MoneyFormatter::format($paymentRequest->amount, $paymentRequest->currency_code) }}

{{ __('Pay now') }}: {{ $payUrl }}
