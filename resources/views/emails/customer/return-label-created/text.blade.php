{{ __('Your return shipping label for order #:id', ['id' => $order->id]) }}

{{ __('A prepaid return shipping label for order #:id is ready. Download it, attach it to your package, and drop off your return.', ['id' => $order->id]) }}
@if ($return->label_url)
{{ __('Download return label') }}: {{ $return->label_url }}
@endif
@if ($return->tracking_number)
{{ __('Tracking number') }}: {{ $return->tracking_number }}
@endif

@if ($viewUrl){{ __('View your return') }}: {{ $viewUrl }}@endif
