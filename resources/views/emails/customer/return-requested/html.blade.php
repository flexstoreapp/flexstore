@extends('emails.layout', ['title' => __('We received your return request for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('We have received your return request and are reviewing it.') }}@endsection

@section('content')
    <h1>{{ __('Your return request was received') }}</h1>
    <p>{{ __('Thanks! We have received your return request for order #:id and our team is reviewing it. We will email you once there is an update.', ['id' => $order->id]) }}</p>

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your return')])
    @endif
@endsection
