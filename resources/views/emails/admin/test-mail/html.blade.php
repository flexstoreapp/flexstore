@extends('emails.layout', ['title' => __('SMTP test email')])

@section('preheader'){{ __('Your mail server is configured correctly.') }}@endsection

@section('content')
    <h1>{{ __('SMTP test email') }}</h1>
    <p>{{ __('If you received this message, your mail server settings are working correctly.') }}</p>
@endsection
