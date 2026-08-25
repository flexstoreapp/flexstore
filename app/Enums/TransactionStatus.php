<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
