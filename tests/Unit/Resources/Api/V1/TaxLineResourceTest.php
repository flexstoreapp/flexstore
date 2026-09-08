<?php

declare(strict_types=1);

use App\Http\Resources\Api\V1\TaxLineResource;
use Illuminate\Http\Request;

covers(TaxLineResource::class);

uses()->group('resources', 'api');

test('a tax line resolves its name and keeps every other field', function (): void {
    $line = (new TaxLineResource([
        'tax_name' => ['en' => 'VAT', 'ar' => 'ضريبة'],
        'rate' => '20.0000',
        'taxable_amount' => '119.9800',
        'tax_amount' => '5.0000',
    ]))->toArray(Request::create('/'));

    expect($line['tax_name'])->toBe('VAT')
        ->and($line['rate'])->toBe('20.0000')
        ->and($line['taxable_amount'])->toBe('119.9800')
        ->and($line['tax_amount'])->toBe('5.0000');
});

test('a name already stored as a plain string is passed through', function (): void {
    $line = (new TaxLineResource(['tax_name' => 'Sales tax', 'tax_amount' => '3.0000']))
        ->toArray(Request::create('/'));

    expect($line['tax_name'])->toBe('Sales tax');
});

test('a line with no name resolves to null rather than leaking a locale map', function (): void {
    $line = (new TaxLineResource(['tax_amount' => '0.0000']))->toArray(Request::create('/'));

    expect($line['tax_name'])->toBeNull();
});

test('the name falls back to the store locale when the active one is missing', function (): void {
    app()->setLocale('ar');

    $line = (new TaxLineResource(['tax_name' => ['en' => 'VAT'], 'tax_amount' => '5.0000']))
        ->toArray(Request::create('/'));

    expect($line['tax_name'])->toBe('VAT');
});
