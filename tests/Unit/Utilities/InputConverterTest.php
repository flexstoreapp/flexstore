<?php

declare(strict_types=1);

use App\Utilities\InputConverter;

covers(InputConverter::class);

uses()->group('utilities');

test('toBoolean passes through real booleans', function () {
    $converter = new InputConverter();

    expect($converter->toBoolean(true))->toBeTrue();
    expect($converter->toBoolean(false))->toBeFalse();
});

test('toBoolean treats common truthy strings as true', function () {
    $converter = new InputConverter();
    $truthyStrings = ['true', 'TRUE', 'True', '1', 'yes', 'YES', 'Yes', 'on', 'ON', 'On'];

    foreach ($truthyStrings as $value) {
        expect($converter->toBoolean($value))->toBeTrue("Failed for value: {$value}");
    }
});

test('toBoolean treats common falsy strings as false', function () {
    $converter = new InputConverter();
    $falsyStrings = ['false', 'FALSE', 'False', '0', 'no', 'NO', 'No', 'off', 'OFF', 'Off'];

    foreach ($falsyStrings as $value) {
        expect($converter->toBoolean($value))->toBeFalse("Failed for value: {$value}");
    }
});

test('toBoolean coerces numeric values', function () {
    $converter = new InputConverter();

    expect($converter->toBoolean(1))->toBeTrue();
    expect($converter->toBoolean(2))->toBeTrue();
    expect($converter->toBoolean(-1))->toBeTrue();
    expect($converter->toBoolean(0))->toBeFalse();
});

test('toBoolean returns false for null and empty values', function () {
    $converter = new InputConverter();

    expect($converter->toBoolean(null))->toBeFalse();
    expect($converter->toBoolean(0))->toBeFalse();
    expect($converter->toBoolean(''))->toBeFalse();
});

test('toBoolean coerces arrays and objects by truthiness', function () {
    $converter = new InputConverter();

    expect($converter->toBoolean([1, 2, 3]))->toBeTrue();
    expect($converter->toBoolean([]))->toBeFalse();

    $object = new stdClass();
    expect($converter->toBoolean($object))->toBeTrue();
});

test('toNullableBoolean returns null for null and empty input', function () {
    $converter = new InputConverter();

    expect($converter->toNullableBoolean(null))->toBeNull();
    expect($converter->toNullableBoolean(''))->toBeNull();
});

test('toNullableBoolean coerces non-empty values', function () {
    $converter = new InputConverter();

    expect($converter->toNullableBoolean(true))->toBeTrue();
    expect($converter->toNullableBoolean(false))->toBeFalse();
    expect($converter->toNullableBoolean('true'))->toBeTrue();
    expect($converter->toNullableBoolean('false'))->toBeFalse();
    expect($converter->toNullableBoolean(1))->toBeTrue();
    expect($converter->toNullableBoolean(0))->toBeFalse();
});

test('toNullableString returns null for null, empty, and whitespace-only input', function () {
    $converter = new InputConverter();

    expect($converter->toNullableString(null))->toBeNull();
    expect($converter->toNullableString(''))->toBeNull();
    expect($converter->toNullableString('   '))->toBeNull();
});

test('toNullableString trims surrounding whitespace', function () {
    $converter = new InputConverter();

    expect($converter->toNullableString('  hello  '))->toBe('hello');
    expect($converter->toNullableString('hello'))->toBe('hello');
});

test('toNullableNumeric returns null for null, empty, or non-numeric input', function () {
    $converter = new InputConverter();

    expect($converter->toNullableNumeric(null))->toBeNull();
    expect($converter->toNullableNumeric(''))->toBeNull();
    expect($converter->toNullableNumeric('abc'))->toBeNull();
});

test('toNullableNumeric preserves numeric input as a string', function () {
    $converter = new InputConverter();

    expect($converter->toNullableNumeric('19.99'))->toBe('19.99');
    expect($converter->toNullableNumeric(42))->toBe('42');
    expect($converter->toNullableNumeric(3.14))->toBe('3.14');
    expect($converter->toNullableNumeric('0'))->toBe('0');
});

test('toIdList returns empty array for null or empty input', function () {
    $converter = new InputConverter();

    expect($converter->toIdList(null))->toBe([]);
    expect($converter->toIdList(''))->toBe([]);
});

test('toIdList parses a comma-separated string of ids', function () {
    $converter = new InputConverter();

    expect($converter->toIdList('1,2,3'))->toBe([1, 2, 3]);
    expect($converter->toIdList('42'))->toBe([42]);
});

test('toIdList accepts arrays of ids', function () {
    $converter = new InputConverter();

    expect($converter->toIdList([1, 2, 3]))->toBe([1, 2, 3]);
    expect($converter->toIdList(['1', '2', '3']))->toBe([1, 2, 3]);
});

test('toIdList filters out zero and non-numeric values', function () {
    $converter = new InputConverter();

    expect($converter->toIdList('1,0,abc,2'))->toBe([1, 2]);
    expect($converter->toIdList([1, 0, 'abc', 2]))->toBe([1, 2]);
});
