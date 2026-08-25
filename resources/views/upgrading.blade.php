{{-- Self-contained: the assets Vite would reference are the ones being replaced. --}}
@php
    $heading = match ($state) {
        'database-missing' => __('Database not found'),
        'upgrade-failed' => __('Update failed'),
        default => __('Update in progress'),
    };

    $message = match ($state) {
        'database-missing' => __('Your database file is missing. Restore it from your backup, then reload this page.'),
        'upgrade-failed' => __('Your store could not finish updating. Check the application log for details.'),
        default => __('Your store is finishing an update. This page refreshes automatically.'),
    };

    $isRtl = \App\Enums\Locale::isRtl(app()->getLocale());
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex" />
        @if ($state === 'upgrading')
            <meta http-equiv="refresh" content="10" />
        @endif
        <title>{{ $heading }}</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px;
                background: #f6f7f9;
                color: #16181d;
                font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
                line-height: 1.6;
            }
            main {
                max-width: 30rem;
                text-align: center;
            }
            h1 {
                margin: 0 0 12px;
                font-size: 20px;
                font-weight: 600;
            }
            p {
                margin: 0;
                color: #5c6270;
            }
            @media (prefers-color-scheme: dark) {
                body {
                    background: #101120;
                    color: #fff;
                }
                p {
                    color: #9aa0aa;
                }
            }
        </style>
    </head>
    <body>
        <main>
            <h1>{{ $heading }}</h1>
            <p>{{ $message }}</p>
        </main>
    </body>
</html>
