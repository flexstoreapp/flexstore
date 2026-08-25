{{ __('Reset your password') }}

{{ __('We received a request to reset the password for the account associated with :email.', ['email' => $recipientEmail]) }}

{{ __('Reset password') }}: {{ $resetUrl }}

{{ trans_choice('This password reset link will expire in :count minute.|This password reset link will expire in :count minutes.', $expiryMinutes) }}

{{ __('If you didn’t request a password reset, you can safely ignore this email — your password will stay the same.') }}
