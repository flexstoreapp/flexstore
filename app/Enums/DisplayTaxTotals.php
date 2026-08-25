<?php

declare(strict_types=1);

namespace App\Enums;

enum DisplayTaxTotals: string
{
    case Itemized = 'itemized';
    case Single = 'single';
}
