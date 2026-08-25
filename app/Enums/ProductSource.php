<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductSource: string
{
    case Latest = 'latest';
    case Featured = 'featured';
    case Category = 'category';
    case Brand = 'brand';
}
