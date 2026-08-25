<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencySymbolPosition: string
{
    case Before = 'before';
    case BeforeWithSpace = 'before_with_space';
    case After = 'after';
    case AfterWithSpace = 'after_with_space';
}
