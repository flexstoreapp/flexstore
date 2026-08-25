{{ __('Verify your email address') }}

{{ __('Welcome! Please confirm that :email belongs to you using the link below.', ['email' => $recipientEmail]) }}

{{ __('Verify email address') }}: {{ $verifyUrl }}

{{ __('If you didn’t create an account, no further action is required.') }}
