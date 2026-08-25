<?php

declare(strict_types=1);

use App\Enums\Currency;

covers(Currency::class);

uses()->group('enums', 'currency');

test('codes method returns array of currency codes', function () {
    $codes = Currency::codes();
    $caseValues = array_map(fn ($c) => $c->name, Currency::cases());

    expect($codes)->toBe($caseValues);
});
