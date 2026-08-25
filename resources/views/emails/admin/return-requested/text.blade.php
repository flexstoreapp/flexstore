{{ __('Return requested for order #:id', ['id' => $order->id]) }}

{{ __('A customer requested a return for order #:id. Review the items below and approve or decline the request.', ['id' => $order->id]) }}

{{ __('Items to return') }}:
@foreach ($return->items as $item)
@if ($item->orderItem)
- {{ $item->orderItem->getTranslation('product_title', app()->getLocale()) }}@if ($item->orderItem->variant_title) ({{ $item->orderItem->variant_title }})@endif x {{ $item->quantity }}
@endif
@endforeach

@if ($viewUrl){{ __('Review the return') }}: {{ $viewUrl }}@endif
