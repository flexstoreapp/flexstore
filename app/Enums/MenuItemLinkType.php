<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuItemLinkType: string
{
    case Brand = 'brand';
    case Category = 'category';
    case Custom = 'custom';
    case Page = 'page';
}
