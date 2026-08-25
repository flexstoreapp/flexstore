{{ __('Update on your return for order #:id', ['id' => $order->id]) }}

{{ __('Unfortunately, your return request for order #:id could not be approved.', ['id' => $order->id]) }}
@if ($return->decline_reason)
{{ __('Reason') }}: {{ $return->decline_reason }}
@endif

@if ($viewUrl){{ __('View your return') }}: {{ $viewUrl }}@endif
