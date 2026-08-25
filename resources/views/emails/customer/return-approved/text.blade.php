{{ __('Your return for order #:id is approved', ['id' => $order->id]) }}

{{ __('Good news! Your return for order #:id has been approved. Please pack the items securely and send them back to us.', ['id' => $order->id]) }}

@if ($return->label_url){{ __('A prepaid return label is ready for you.') }}
{{ __('Download return label') }}: {{ $return->label_url }}

@endif
@if ($viewUrl){{ __('View your return') }}: {{ $viewUrl }}@endif
