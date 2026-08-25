@extends('emails.layout', ['title' => __('Order #:id updated', ['id' => $order->id])])

@section('preheader'){{ __('There’s an update on your order.') }}@endsection

@section('content')
    <h1>{{ __('Your order has been updated') }}</h1>
    <p>{{ __('Order #:id has new details. Tap below to see what changed.', ['id' => $order->id]) }}</p>

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your order')])
    @endif

    <h2>{{ __('Order summary') }}</h2>
    @include('emails.components.order-totals', ['order' => $order])
@endsection
