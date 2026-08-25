<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingGroup: string
{
    case General = 'general';
    case Store = 'store';
    case Locale = 'locale';
    case Currency = 'currency';
    case Tax = 'tax';
    case Checkout = 'checkout';
    case Newsletter = 'newsletter';
    case Mail = 'mail';
    case Notification = 'notification';
    case Policy = 'policy';
    case Seo = 'seo';
    case Integration = 'integration';
    case System = 'system';
    case Storefront = 'storefront';
    case Shipping = 'shipping';
}
