<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementReason: string
{
    case Manual = 'manual';
    case Received = 'received';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Return = 'return';
    case InventoryCount = 'inventory_count';
    case Transfer = 'transfer';
    case Sale = 'sale';
    case Refund = 'refund';
    case Cancellation = 'cancellation';
    case Other = 'other';
}
