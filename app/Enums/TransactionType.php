<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Sale = 'sale';
    case Void = 'void';
    case Refund = 'refund';
}
