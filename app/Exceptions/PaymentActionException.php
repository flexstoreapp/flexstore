<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class PaymentActionException extends Exception
{
    public static function noPaymentGateway(): self
    {
        return new self(__('No payment gateway associated with this order.'));
    }

    public static function onlyManualSupportsVoiding(): self
    {
        return new self(__('Only manual payment methods support voiding.'));
    }

    public static function noRecordedPayments(): self
    {
        return new self(__('This order has no recorded payments to void.'));
    }

    public static function noOutstandingBalance(): self
    {
        return new self(__('This order has no outstanding balance.'));
    }

    public static function cannotRequestManualPayment(): self
    {
        return new self(__('Cannot request payment for manual payment methods.'));
    }

    public static function missingCustomerEmail(): self
    {
        return new self(__('Order has no customer email address.'));
    }

    public static function noCreditOwed(): self
    {
        return new self(__('This order has no credit owed.'));
    }

    public function render(): void
    {
        abort(422, $this->getMessage());
    }
}
