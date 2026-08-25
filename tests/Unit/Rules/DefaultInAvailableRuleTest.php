<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Rules\DefaultInAvailableRule;

covers(DefaultInAvailableRule::class);

uses()->group('rules', 'language');

test('passes when the value is among the available options', function () {
    $rule = (new DefaultInAvailableRule('available_locales'))->setData([
        'available_locales' => ['en', 'bn'],
    ]);

    $failed = false;
    $rule->validate('default_locale', 'en', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when the value is not among the available options', function () {
    $rule = (new DefaultInAvailableRule('available_locales'))->setData([
        'available_locales' => ['en', 'bn'],
    ]);

    $failed = false;
    $rule->validate('default_locale', 'ar', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('falls back to the stored setting when the available field is absent from the request data', function () {
    Setting::setValue('available_locales', ['en']);

    $failed = false;
    (new DefaultInAvailableRule('available_locales'))->validate('default_locale', 'ar', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});
