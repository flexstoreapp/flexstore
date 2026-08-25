<?php

declare(strict_types=1);

namespace App\Enums;

enum WeightUnit: string
{
    case Kg = 'kg';
    case G = 'g';
    case Lb = 'lb';
    case Oz = 'oz';
}
