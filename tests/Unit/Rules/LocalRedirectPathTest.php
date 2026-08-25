<?php

declare(strict_types=1);

use App\Rules\LocalRedirectPath;
use Illuminate\Support\Facades\Validator;

covers(LocalRedirectPath::class);

uses()->group('rules');

function validateRedirectPath(mixed $value): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(['redirect' => $value], ['redirect' => [new LocalRedirectPath()]]);
}

test('passes for a local path', function () {
    expect(validateRedirectPath('/account/orders')->passes())->toBeTrue();
});

test('passes when the value is blank', function (mixed $value) {
    expect(validateRedirectPath($value)->passes())->toBeTrue();
})->with([null, '', '   ']);

test('fails for protocol relative and absolute urls', function (string $value) {
    expect(validateRedirectPath($value)->passes())->toBeFalse();
})->with([
    '//evil.test/path',
    'https://evil.test/path',
    'javascript://evil.test',
]);

test('fails for a path that does not start with a slash', function () {
    expect(validateRedirectPath('account/orders')->passes())->toBeFalse();
});

test('fails for a path containing a backslash', function () {
    expect(validateRedirectPath('/account\\..\\admin')->passes())->toBeFalse();
});

test('fails for a non string value', function () {
    expect(validateRedirectPath(['/account'])->passes())->toBeFalse();
});

test('reports a translated failure message', function () {
    $validator = validateRedirectPath('https://evil.test');

    expect($validator->errors()->first('redirect'))
        ->toBe(__('The :attribute must be a valid local path.', ['attribute' => 'redirect']));
});
