@php
    $label = $label ?? null;
@endphp
@php
    $countryName = $address->country_code
        ? (\App\Enums\Country::fromNameOrValue($address->country_code)?->value ?? $address->country_code)
        : null;
@endphp
<div>
    @if ($label)
        <div style="font-size:11px; text-transform:uppercase; color:#6b7280; font-weight:600; margin-bottom:6px;">{{ $label }}</div>
    @endif
    <div style="font-size:13px; color:#1f2937; line-height:1.5;">
        <div style="font-weight:600; color:#0f172a;">{{ trim($address->first_name.' '.$address->last_name) }}</div>
        <div>{{ $address->address_line_1 }}</div>
        @if ($address->address_line_2)
            <div>{{ $address->address_line_2 }}</div>
        @endif
        <div>{{ trim(implode(', ', array_filter([$address->city, $address->state_name ?? $address->state]))) }} {{ $address->postal_code }}</div>
        @if ($countryName)
            <div>{{ $countryName }}</div>
        @endif
        @if ($address->phone)
            <div style="color:#6b7280; margin-top:4px;"><bdi>{{ $address->phone }}</bdi></div>
        @endif
    </div>
</div>
