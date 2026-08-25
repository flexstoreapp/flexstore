{{ __('Order #:id canceled', ['id' => $order->id]) }}

{{ __('Order #:id has been canceled. If a payment was made, the refund will be processed separately.', ['id' => $order->id]) }}

@if ($order->cancellation_note)
{{ __('Reason') }}: {{ $order->cancellation_note }}

@endif
@if ($viewUrl){{ __('View your order') }}: {{ $viewUrl }}@endif
