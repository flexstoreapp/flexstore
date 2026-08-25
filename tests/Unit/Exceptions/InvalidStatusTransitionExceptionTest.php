<?php

declare(strict_types=1);

use App\Exceptions\InvalidStatusTransitionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(InvalidStatusTransitionException::class);

uses()->group('exceptions');

test('make builds a descriptive message', function () {
    $exception = InvalidStatusTransitionException::make('payment', 'paid', 'unpaid');

    expect($exception->getMessage())->toBe("Invalid payment status transition from 'paid' to 'unpaid'.");
});

test('render aborts with http 409', function () {
    $exception = InvalidStatusTransitionException::make('fulfillment', 'unfulfilled', 'fulfilled');

    try {
        $exception->render();
        expect()->fail('Expected HttpException to be thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(409)
            ->and($e->getMessage())->toContain('Invalid fulfillment status transition');
    }
});
