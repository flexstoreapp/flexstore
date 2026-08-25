@extends('emails.layout', ['title' => __('Verify your email address')])

@section('preheader'){{ __('Confirm your email to activate your account.') }}@endsection

@section('content')
    <h1>{{ __('Verify your email address') }}</h1>
    <p>{{ __('Welcome! Please confirm that :email belongs to you by clicking the button below.', ['email' => $recipientEmail]) }}</p>

    @include('emails.components.button', ['url' => $verifyUrl, 'label' => __('Verify email address')])

    <p class="es-muted" style="color:#6b7280; font-size:13px;">{{ __('If you didn’t create an account, no further action is required.') }}</p>
@endsection
