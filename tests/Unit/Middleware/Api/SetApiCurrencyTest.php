<?php

declare(strict_types=1);

use App\Http\Middleware\Api\SetApiCurrency;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(SetApiCurrency::class);

uses()->group('middleware', 'api');

function handleApiCurrency(?string $header): Request
{
    $request = Request::create('/api/v1/products');

    if ($header !== null) {
        $request->headers->set(SetApiCurrency::HEADER, $header);
    }

    (new SetApiCurrency())->handle($request, fn (): Response => new Response());

    return $request;
}

beforeEach(function (): void {
    Setting::setValue('base_currency', 'USD');
    Currency::query()->delete();
});

test('an active currency from the header is applied', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    expect(handleApiCurrency('EUR')->attributes->get('active_currency'))->toBe('EUR');
});

test('the header is case insensitive', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    expect(handleApiCurrency('eur')->attributes->get('active_currency'))->toBe('EUR');
});

test('an inactive currency falls back to the base currency', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'EUR', 'is_active' => false]);

    expect(handleApiCurrency('EUR')->attributes->get('active_currency'))->toBe('USD');
});

test('a missing header falls back to the base currency', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    expect(handleApiCurrency(null)->attributes->get('active_currency'))->toBe('USD');
});

test('the base currency is used when no currency rows exist', function (): void {
    expect(handleApiCurrency('EUR')->attributes->get('active_currency'))->toBe('USD');
});
