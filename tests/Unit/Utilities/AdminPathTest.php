<?php

declare(strict_types=1);

namespace Tests\Unit\Utilities;

use App\Utilities\AdminPath;
use Illuminate\Http\Request;
use InvalidArgumentException;

covers(AdminPath::class);

uses()->group('utilities', 'admin-path');

test('defaults to admin', function () {
    config(['admin.prefix' => 'admin']);

    expect(AdminPath::prefix())->toBe('admin');
});

test('uses the configured prefix', function () {
    config(['admin.prefix' => 'control-panel']);

    expect(AdminPath::prefix())->toBe('control-panel');
});

test('trims surrounding slashes from the configured prefix', function () {
    config(['admin.prefix' => '/manage/']);

    expect(AdminPath::prefix())->toBe('manage');
});

test('falls back to admin when the configured prefix is blank', function () {
    config(['admin.prefix' => '']);

    expect(AdminPath::prefix())->toBe('admin');
});

test('keeps a numeric prefix', function () {
    config(['admin.prefix' => '0']);

    expect(AdminPath::prefix())->toBe('0');
});

test('rejects a prefix that is not a single path segment', function (string $prefix) {
    config(['admin.prefix' => $prefix]);

    expect(fn () => AdminPath::prefix())->toThrow(InvalidArgumentException::class);
})->with(['my/panel', 'panel space', 'pa..nel', '-panel', 'pänel']);

test('rejects a prefix reserved by the storefront', function (string $prefix) {
    config(['admin.prefix' => $prefix]);

    expect(fn () => AdminPath::prefix())->toThrow(InvalidArgumentException::class);
})->with(['products', 'cart', 'checkout', 'account', 'Products']);

test('matches the prefix root and its children', function (string $path) {
    config(['admin.prefix' => 'manage']);

    expect(AdminPath::matches(Request::create($path)))->toBeTrue();
})->with(['/manage', '/manage/orders', '/manage/settings/store']);

test('does not match storefront paths', function (string $path) {
    config(['admin.prefix' => 'manage']);

    expect(AdminPath::matches(Request::create($path)))->toBeFalse();
})->with(['/', '/products', '/admin', '/management']);
