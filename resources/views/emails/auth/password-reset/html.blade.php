@extends('emails.layout', ['title' => __('Reset your password')])

@section('preheader'){{ __('Use the button below to set a new password.') }}@endsection

@section('content')
    <h1>{{ __('Reset your password') }}</h1>
    <p>{{ __('We received a request to reset the password for the account associated with :email.', ['email' => $recipientEmail]) }}</p>

    @include('emails.components.button', ['url' => $resetUrl, 'label' => __('Reset password')])

    <p class="es-muted" style="color:#6b7280; font-size:13px;">{{ trans_choice('This password reset link will expire in :count minute.|This password reset link will expire in :count minutes.', $expiryMinutes) }}</p>
    <p class="es-muted" style="color:#6b7280; font-size:13px;">{{ __('If you didn’t request a password reset, you can safely ignore this email — your password will stay the same.') }}</p>
@endsection
