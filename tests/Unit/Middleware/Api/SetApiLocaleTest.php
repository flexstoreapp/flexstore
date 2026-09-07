<?php

declare(strict_types=1);

use App\Http\Middleware\Api\SetApiLocale;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(SetApiLocale::class);

uses()->group('middleware', 'api');

function setApiLocaleSettings(array $available, string $default): void
{
    Setting::setValue('available_locales', $available);
    Setting::setValue('default_locale', $default);
}

function handleApiLocale(?string $header): Request
{
    $request = Request::create('/api/v1/products');

    // Request::create() injects a default Accept-Language, which a real API client may not send.
    $header === null
        ? $request->headers->remove('Accept-Language')
        : $request->headers->set('Accept-Language', $header);

    (new SetApiLocale())->handle($request, fn (): Response => new Response());

    return $request;
}

test('an available locale from the header is applied', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'en');

    handleApiLocale('ar');

    expect(app()->getLocale())->toBe('ar');
});

test('a locale the store does not offer falls back to the default', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'en');

    handleApiLocale('fr');

    expect(app()->getLocale())->toBe('en');
});

test('a missing header falls back to the default', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'ar');

    handleApiLocale(null);

    expect(app()->getLocale())->toBe('ar');
});

test('a quality weighted header picks the best available match', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'en');

    handleApiLocale('fr;q=0.9, ar;q=0.8');

    expect(app()->getLocale())->toBe('ar');
});

test('the fallback locale is always the store default', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'ar');

    handleApiLocale('en');

    expect(app()->getLocale())->toBe('en')
        ->and(app()->getFallbackLocale())->toBe('ar');
});

test('the available locales are exposed to the rest of the request', function (): void {
    setApiLocaleSettings(['en', 'ar'], 'en');

    $request = handleApiLocale('en');

    expect($request->attributes->get('available_locales'))->toBe(['en', 'ar']);
});
