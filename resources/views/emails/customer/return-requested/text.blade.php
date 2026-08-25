{{ __('We received your return request for order #:id', ['id' => $order->id]) }}

{{ __('Thanks! We have received your return request for order #:id and our team is reviewing it. We will email you once there is an update.', ['id' => $order->id]) }}

@if ($viewUrl){{ __('View your return') }}: {{ $viewUrl }}@endif
