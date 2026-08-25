<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Failed = 'failed';
    case Canceled = 'canceled';

    /**
     * @return array<self>
     */
    public static function eligibleForReview(): array
    {
        return [
            self::PartiallyPaid,
            self::Paid,
            self::PartiallyRefunded,
        ];
    }
}
