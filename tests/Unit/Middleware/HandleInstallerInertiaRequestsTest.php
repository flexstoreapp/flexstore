<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInstallerInertiaRequests;
use Illuminate\Http\Request;

covers(HandleInstallerInertiaRequests::class);

uses()->group('middleware', 'installer');

test('share includes appName from config', function () {
    config()->set('app.name', 'Flex Test Store');

    $middleware = new HandleInstallerInertiaRequests();

    $shared = $middleware->share(Request::create('/install'));

    expect($shared)->toHaveKey('appName', 'Flex Test Store');
});

test('uses installer as root view', function () {
    $middleware = new HandleInstallerInertiaRequests();

    $reflection = new ReflectionClass($middleware);
    $property = $reflection->getProperty('rootView');
    $property->setAccessible(true);

    expect($property->getValue($middleware))->toBe('installer');
});
