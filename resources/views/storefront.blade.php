<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-default-locale="{{ $page['props']['defaultLocale'] ?? config('app.locale', 'en') }}"
    dir="{{ $page['props']['direction'] ?? 'ltr' }}"
>
    <head>
        @php
            $storefrontTokens = \App\Enums\StorefrontAccent::fromValue($page['props']['theme'] ?? null)->tokens();
        @endphp
        <style>
            html {
                background-color: oklch(0.969 0 0);
            }

            :root {
                --color-primary: {{ $storefrontTokens['primary'] }};
                --color-primary-hover: {{ $storefrontTokens['hover'] }};
                --color-primary-tint: {{ $storefrontTokens['tint'] }};
            }
        </style>

        @include('partials.head-meta', ['translationBundle' => \App\Utilities\Translations::STOREFRONT])
        @head

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=jost:400,500,600,700|public-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @php
            $integrationSettings = \App\Models\Setting::getByGroup(\App\Enums\SettingGroup::Integration);
            $gtmId = $integrationSettings->get('integration_google_tag_manager_id');
            $gaId = $integrationSettings->get('integration_google_analytics_id');
            $metaPixelId = $integrationSettings->get('integration_meta_pixel_id');
            $tiktokPixelId = $integrationSettings->get('integration_tiktok_pixel_id');
            $pinterestTagId = $integrationSettings->get('integration_pinterest_tag_id');
        @endphp

        @if ($gtmId)
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
        @elseif ($gaId)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
        @endif

        @if ($metaPixelId)
            <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $metaPixelId }}');fbq('track','PageView');</script>
            <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" /></noscript>
        @endif

        @if ($tiktokPixelId)
            <script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src=r+"?sdkid="+e+"&lib="+t;var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(a,s)};ttq.load('{{ $tiktokPixelId }}');ttq.page();}(window,document,'ttq');</script>
        @endif

        @if ($pinterestTagId)
            <script>!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");pintrk('load','{{ $pinterestTagId }}');pintrk('page');</script>
            <noscript><img height="1" width="1" style="display:none" alt="" src="https://ct.pinterest.com/v3/?event=init&tid={{ $pinterestTagId }}&noscript=1" /></noscript>
        @endif

        @viteReactRefresh
        @vite(['resources/css/storefront.css', 'resources/js/storefront.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="min-w-[320px]">
        @if (!empty($gtmId))
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
        @endif
        @inertia
    </body>
</html>
