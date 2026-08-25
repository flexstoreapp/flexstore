<?php

declare(strict_types=1);

use App\Exceptions\OrderNotRefundableException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(OrderNotRefundableException::class);

uses()->group('exceptions', 'refund');

test('carries the default message', function () {
    expect((new OrderNotRefundableException())->getMessage())->toBe('This order cannot be refunded.');
});

test('render aborts with a 410 gone', function () {
    try {
        (new OrderNotRefundableException())->render();
        $this->fail('Expected an HttpException to be thrown.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(410)
            ->and($exception->getMessage())->toBe('This order cannot be refunded.');
    }
});
