<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuLocation: string
{
    case Header = 'header';
    case Footer = 'footer';
}
