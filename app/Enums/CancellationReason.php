<?php

declare(strict_types=1);

namespace App\Enums;

enum CancellationReason: string
{
    case CustomerRequest = 'customer_request';
    case Fraudulent = 'fraudulent';
    case Inventory = 'inventory';
    case Other = 'other';
}
