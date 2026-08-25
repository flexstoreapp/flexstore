<?php

declare(strict_types=1);

namespace App\Enums;

enum Appearance: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}
