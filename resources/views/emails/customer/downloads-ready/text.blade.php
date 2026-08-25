{{ __('Your downloads for order #:id are ready', ['id' => $order->id]) }}

{{ __('Payment for order #:id has been received. Your digital items are ready to download.', ['id' => $order->id]) }}
@if (! empty($downloads ?? []))

{{ __('Downloads') }}:
@foreach ($downloads as $download)
- {{ $download['name'] }}: {{ $download['url'] }}
@endforeach
@endif

@if ($viewUrl){{ __('View your order') }}: {{ $viewUrl }}@endif
