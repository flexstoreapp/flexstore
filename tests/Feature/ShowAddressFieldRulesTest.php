<?php

declare(strict_types=1);

use App\Address\AddressFieldRules;
use App\Http\Controllers\AddressFieldRulesController;

use function Pest\Laravel\get;

covers(AddressFieldRulesController::class, AddressFieldRules::class);

uses()->group('address');

it('returns the Bangladesh address format with districts and hidden postal_code', function (): void {
    $response = get(route('address-field-rules.show', 'BD'));

    $response->assertOk()
        ->assertJsonPath('country_code', 'BD')
        ->assertJsonPath('state.label', 'District')
        ->assertJsonPath('postal_code.hidden', true)
        ->assertJsonPath('postal_code.required', false)
        ->assertJsonCount(64, 'state.options');
});

it('returns the US address format with a state dropdown and ZIP label', function (): void {
    $response = get(route('address-field-rules.show', 'us'));

    $response->assertOk()
        ->assertJsonPath('country_code', 'US')
        ->assertJsonPath('state.label', 'State')
        ->assertJsonPath('postal_code.label', 'ZIP code')
        ->assertJsonCount(51, 'state.options');
});

it('falls back to defaults for an unknown country', function (): void {
    $response = get(route('address-field-rules.show', 'ZZ'));

    $response->assertOk()
        ->assertJsonPath('state.options', null)
        ->assertJsonPath('state.required', false)
        ->assertJsonPath('postal_code.required', true);
});

it('sends caching headers and honours conditional requests', function (): void {
    $response = get(route('address-field-rules.show', 'BD'));

    $etag = $response->headers->get('ETag');
    expect($etag)->not->toBeNull();
    expect($response->headers->get('Cache-Control'))->toContain('max-age=86400');

    get(route('address-field-rules.show', 'BD'), ['If-None-Match' => $etag])
        ->assertStatus(304);
});
