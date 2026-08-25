{{ __('Tracking updated for order #:id', ['id' => $order->id]) }}

{{ __('We’ve updated the tracking information for order #:id.', ['id' => $order->id]) }}

@if ($shipment->tracking_number)
{{ __('Tracking') }}: {{ $shipment->tracking_number }}@if ($shipment->tracking_url) ({{ $shipment->tracking_url }})@endif
@endif
@if ($viewUrl)

{{ __('View your order') }}: {{ $viewUrl }}
@endif
