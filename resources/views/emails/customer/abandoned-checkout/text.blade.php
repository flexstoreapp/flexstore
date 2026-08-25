{{ $heading }}

{{ $body }}

@if ((float) $session->discount_total > 0)
{{ __('Discount') }}@if ($session->coupon_code) ({{ $session->coupon_code }})@endif: -{{ \App\Utilities\MoneyFormatter::format($session->discount_total, $session->currency_code ?? 'USD') }}

@endif
{{ __('Cart total') }}: {{ \App\Utilities\MoneyFormatter::format($session->total, $session->currency_code ?? 'USD') }}

@if ($discountOffer)
{{ $discountOffer }}

@endif
{{ __('Complete your order') }}: {{ $recoveryUrl }}

{{ __('This link will restore your cart so you can pick up where you left off.') }}
