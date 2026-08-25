<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckoutMode: string
{
    case Embedded = 'embedded';
    case Hosted = 'hosted';
}
