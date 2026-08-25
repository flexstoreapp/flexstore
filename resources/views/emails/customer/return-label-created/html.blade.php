@extends('emails.layout', ['title' => __('Your return shipping label for order #:id', ['id' => $order->id])])

@section('preheader'){{ __('Your prepaid return label is ready.') }}@endsection

@section('content')
    <h1>{{ __('Your return label is ready') }}</h1>
    <p>{{ __('A prepaid return shipping label for order #:id is ready. Download it, attach it to your package, and drop off your return.', ['id' => $order->id]) }}</p>

    @if ($return->label_url)
        @include('emails.components.button', ['url' => $return->label_url, 'label' => __('Download return label')])
    @endif

    @if ($return->tracking_number)
        <p style="font-size:13px; color:#6b7280;">{{ __('Tracking number') }}: <span dir="ltr" style="color:#1f2937;">{{ $return->tracking_number }}</span></p>
    @endif

    @if ($viewUrl)
        @include('emails.components.button', ['url' => $viewUrl, 'label' => __('View your return')])
    @endif
@endsection
