<?php

declare(strict_types=1);

namespace App\Enums;

enum TokenAbility: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case TwoFactorPending = 'two-factor-pending';
}
