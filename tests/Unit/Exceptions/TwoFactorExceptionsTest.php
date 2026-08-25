<?php

declare(strict_types=1);

use App\Exceptions\TwoFactorConfirmationNotPendingException;
use App\Exceptions\TwoFactorNotEnabledException;
use App\Exceptions\TwoFactorSetupNotStartedException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(TwoFactorConfirmationNotPendingException::class);
covers(TwoFactorNotEnabledException::class);
covers(TwoFactorSetupNotStartedException::class);

uses()->group('exceptions', 'two-factor');

test('TwoFactorConfirmationNotPendingException has the expected default message', function () {
    expect((new TwoFactorConfirmationNotPendingException())->getMessage())
        ->toBe('Two-factor authentication confirmation is not pending.');
});

test('TwoFactorConfirmationNotPendingException renders 403', function () {
    try {
        (new TwoFactorConfirmationNotPendingException())->render();
        expect()->fail('Expected HttpException to be thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getMessage())->toBe('Two-factor authentication confirmation is not pending.');
    }
});

test('TwoFactorNotEnabledException has the expected default message', function () {
    expect((new TwoFactorNotEnabledException())->getMessage())
        ->toBe('Two-factor authentication is not enabled for this user.');
});

test('TwoFactorNotEnabledException renders 403', function () {
    try {
        (new TwoFactorNotEnabledException())->render();
        expect()->fail('Expected HttpException to be thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

test('TwoFactorSetupNotStartedException has the expected default message', function () {
    expect((new TwoFactorSetupNotStartedException())->getMessage())
        ->toBe('Two-factor authentication setup has not been started.');
});

test('TwoFactorSetupNotStartedException renders 403', function () {
    try {
        (new TwoFactorSetupNotStartedException())->render();
        expect()->fail('Expected HttpException to be thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});
