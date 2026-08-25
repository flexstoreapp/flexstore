<?php

declare(strict_types=1);

use App\Rules\ValidLocale;

covers(ValidLocale::class);

uses()->group('rules', 'language');

test('passes for a locale that has a translation file', function () {
    $failed = false;
    (new ValidLocale())->validate('default_locale', 'en', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails for a locale without a translation file', function () {
    $failed = false;
    (new ValidLocale())->validate('default_locale', 'xx', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});
