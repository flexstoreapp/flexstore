<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingType: string
{
    case Text = 'text';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Array = 'array';
    case Asset = 'asset';
    case Encrypted = 'encrypted';
}
