<?php

declare(strict_types=1);

namespace App\Enums;

enum StorePolicy: string
{
    case Refund = 'refund';
    case Privacy = 'privacy';
    case Terms = 'terms';

    public function settingKey(): string
    {
        return match ($this) {
            self::Refund => 'refund_policy',
            self::Privacy => 'privacy_policy',
            self::Terms => 'terms_of_service',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Refund => __('Refund policy'),
            self::Privacy => __('Privacy policy'),
            self::Terms => __('Terms of service'),
        };
    }
}
