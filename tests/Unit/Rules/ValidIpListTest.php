<?php

declare(strict_types=1);

use App\Rules\ValidIpList;

covers(ValidIpList::class);

uses()->group('rules');

test('accepts valid IPv4 addresses', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', "192.168.1.1\n10.0.0.1\n172.16.0.1", function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('accepts valid IPv6 addresses', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', "::1\n2001:db8::1", function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('accepts single IP address', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', '192.168.1.1', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('rejects invalid IP address', function () {
    $rule = new ValidIpList;
    $message = '';

    $rule->validate('ips', "192.168.1.1\nnot-an-ip", function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toContain('not-an-ip');
});

test('rejects non-string value', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', 123, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('ignores empty lines', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', "192.168.1.1\n\n\n10.0.0.1", function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('trims whitespace from IPs', function () {
    $rule = new ValidIpList;
    $failed = false;

    $rule->validate('ips', "  192.168.1.1  \n  10.0.0.1  ", function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
