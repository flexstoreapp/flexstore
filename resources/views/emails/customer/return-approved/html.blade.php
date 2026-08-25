@extends('emails.layout', ['title' => __('Your return for order #:id is approved', ['id' => $order->id])])

@section('preheader'){{ __('Your return has been approved.') }}@endsection

@section('content')
    <h1>{{ __('Your return is approved') }}</h1>
    <p>{{ __('Good news! Your return for order #:id has been approved. Please pack the items securely and send them back to us.', ['id' => $order->id]) }}</p>

    @if ($return->label_url)
        <p>{{ __('A prepaid return label is ready for you.') }}</p>
        @include('emails.components.button', ['url' => $return->label_url, 'label' => __('Download return label')])
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your return')])
    @endif
@endsection
