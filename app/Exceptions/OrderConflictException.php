<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class OrderConflictException extends Exception
{
    public static function cannotCancelFulfillmentForCanceledOrder(): self
    {
        return new self(__('Cannot cancel fulfillment for canceled orders.'));
    }

    public static function cannotCreateShipmentForClosedOrder(): self
    {
        return new self(__('Cannot create shipments for canceled or on-hold orders.'));
    }

    public static function cannotUpdateShipmentForCanceledOrder(): self
    {
        return new self(__('Cannot update shipments for canceled orders.'));
    }

    public static function orderAlreadyCanceled(): self
    {
        return new self(__('Order is already canceled.'));
    }

    public static function fulfilledOrderCannotBeCanceled(): self
    {
        return new self(__('Fulfilled orders cannot be canceled.'));
    }

    public function render(): void
    {
        abort(409, $this->getMessage());
    }
}
