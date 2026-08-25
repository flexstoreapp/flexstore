<?php

declare(strict_types=1);

use App\Exceptions\PaymentActionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(PaymentActionException::class);

uses()->group('exceptions', 'payment');

test('each named constructor carries its message', function (PaymentActionException $exception, string $message) {
    expect($exception->getMessage())->toBe($message);
})->with([
    [fn () => PaymentActionException::noPaymentGateway(), 'No payment gateway associated with this order.'],
    [fn () => PaymentActionException::onlyManualSupportsVoiding(), 'Only manual payment methods support voiding.'],
    [fn () => PaymentActionException::noRecordedPayments(), 'This order has no recorded payments to void.'],
    [fn () => PaymentActionException::noOutstandingBalance(), 'This order has no outstanding balance.'],
    [fn () => PaymentActionException::cannotRequestManualPayment(), 'Cannot request payment for manual payment methods.'],
    [fn () => PaymentActionException::missingCustomerEmail(), 'Order has no customer email address.'],
    [fn () => PaymentActionException::noCreditOwed(), 'This order has no credit owed.'],
]);

test('render aborts with a 422 unprocessable entity', function () {
    try {
        PaymentActionException::noCreditOwed()->render();
        $this->fail('Expected an HttpException to be thrown.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422)
            ->and($exception->getMessage())->toBe('This order has no credit owed.');
    }
});
