<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Customer = 'Customer';
}
