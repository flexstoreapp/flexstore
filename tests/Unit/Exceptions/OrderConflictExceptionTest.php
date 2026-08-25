<?php

declare(strict_types=1);

use App\Exceptions\OrderConflictException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(OrderConflictException::class);

uses()->group('exceptions', 'order');

test('each named constructor carries its message', function (OrderConflictException $exception, string $message) {
    expect($exception->getMessage())->toBe($message);
})->with([
    [fn () => OrderConflictException::cannotCancelFulfillmentForCanceledOrder(), 'Cannot cancel fulfillment for canceled orders.'],
    [fn () => OrderConflictException::cannotCreateShipmentForClosedOrder(), 'Cannot create shipments for canceled or on-hold orders.'],
    [fn () => OrderConflictException::cannotUpdateShipmentForCanceledOrder(), 'Cannot update shipments for canceled orders.'],
    [fn () => OrderConflictException::orderAlreadyCanceled(), 'Order is already canceled.'],
    [fn () => OrderConflictException::fulfilledOrderCannotBeCanceled(), 'Fulfilled orders cannot be canceled.'],
]);

test('render aborts with a 409 conflict', function () {
    try {
        OrderConflictException::orderAlreadyCanceled()->render();
        $this->fail('Expected an HttpException to be thrown.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409)
            ->and($exception->getMessage())->toBe('Order is already canceled.');
    }
});
