{{ __('New review for :product', ['product' => $productTitle]) }}

{{ __('Rating') }}: {{ $review->rating }}/5
@if (! empty($review->content))

{{ $review->content }}
@endif

{{ __('Moderate reviews') }}: {{ $viewUrl }}
