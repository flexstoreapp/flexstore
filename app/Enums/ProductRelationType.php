<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductRelationType: string
{
    case CrossSell = 'cross_sell';
    case UpSell = 'up_sell';
}
