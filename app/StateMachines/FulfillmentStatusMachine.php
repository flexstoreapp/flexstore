<?php

declare(strict_types=1);

namespace App\StateMachines;

use App\Enums\FulfillmentStatus;
use App\Exceptions\InvalidStatusTransitionException;

final readonly class FulfillmentStatusMachine
{
    /**
     * @var array<string, list<FulfillmentStatus>>
     */
    private const array TRANSITIONS = [
        'unfulfilled' => [FulfillmentStatus::InProgress, FulfillmentStatus::Fulfilled, FulfillmentStatus::OnHold],
        'in_progress' => [FulfillmentStatus::Unfulfilled, FulfillmentStatus::Fulfilled, FulfillmentStatus::OnHold],
        'on_hold' => [FulfillmentStatus::InProgress, FulfillmentStatus::Unfulfilled],
        'fulfilled' => [FulfillmentStatus::Unfulfilled, FulfillmentStatus::InProgress],
    ];

    public static function canTransition(FulfillmentStatus $from, FulfillmentStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value];

        return in_array($to, $allowed, true);
    }

    /**
     * @throws InvalidStatusTransitionException
     */
    public static function assertCanTransition(FulfillmentStatus $from, FulfillmentStatus $to): void
    {
        if (! self::canTransition($from, $to)) {
            throw InvalidStatusTransitionException::make('fulfillment', $from->value, $to->value);
        }
    }
}
