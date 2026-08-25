<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

@php
    $favicon = \App\Models\Setting::getValue('store_favicon');
    $translationLocale = \App\Utilities\Translations::resolveLocale(app()->getLocale());
@endphp

@if ($favicon && ($favicon['url'] ?? null))
    <link rel="icon" href="{{ $favicon['url'] }}">
    <link rel="apple-touch-icon" href="{{ $favicon['url'] }}">
@endif

<script type="application/json" id="app-translations">@json(\App\Utilities\Translations::bundle($translationBundle, $translationLocale))</script>
