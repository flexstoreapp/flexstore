<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-default-locale="{{ $page['props']['defaultLocale'] ?? config('app.locale', 'en') }}"
    dir="{{ $page['props']['direction'] ?? 'ltr' }}"
    @class(['dark' => ($page['props']['appearance'] ?? 'system') === 'dark'])
>
    <head>
        <script>
            (function() {
                const appearance = '{{ $page['props']['appearance'] ?? "system" }}';

                if (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @php
            $adminThemeTokens = config(
                'admin-themes.' . ($page['props']['adminThemeColor'] ?? 'neutral'),
                config('admin-themes.neutral'),
            );
        @endphp

        <style>
            html {
                background-color: {{ $adminThemeTokens['light']['background'] }};
            }

            html.dark {
                background-color: {{ $adminThemeTokens['dark']['background'] }};
            }
        </style>

        <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#1c1c1c" media="(prefers-color-scheme: dark)">

        <title data-inertia>{{ config('app.name', 'FlexStore') }}</title>

        @include('partials.head-meta', ['translationBundle' => \App\Utilities\Translations::ADMIN])
        @head

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/css/admin.css', 'resources/js/admin.tsx', "resources/js/pages/{$page['component']}.tsx"])

        <style>
            :root {
                @foreach ($adminThemeTokens['light'] as $token => $value)
                --{{ $token }}: {{ $value }};
                @endforeach
            }

            .dark {
                @foreach ($adminThemeTokens['dark'] as $token => $value)
                --{{ $token }}: {{ $value }};
                @endforeach
            }
        </style>

        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
