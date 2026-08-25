<?php

declare(strict_types=1);

namespace App\Enums;

enum AdminThemeColor: string
{
    case Neutral = 'neutral';
    case Gray = 'gray';
    case Red = 'red';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Sky = 'sky';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Purple = 'purple';
    case Fuchsia = 'fuchsia';
    case Pink = 'pink';

    public static function hexFor(string $value): string
    {
        return (self::tryFrom($value) ?? self::Neutral)->hex();
    }

    public static function darkHexFor(string $value): string
    {
        return (self::tryFrom($value) ?? self::Neutral)->darkHex();
    }

    /**
     * Tailwind 900 shade
     */
    public function hex(): string
    {
        return match ($this) {
            self::Neutral => '#171717',
            self::Gray => '#111827',
            self::Red => '#7f1d1d',
            self::Orange => '#7c2d12',
            self::Yellow => '#713f12',
            self::Lime => '#365314',
            self::Emerald => '#064e3b',
            self::Teal => '#134e4a',
            self::Cyan => '#164e63',
            self::Sky => '#0c4a6e',
            self::Blue => '#1e3a8a',
            self::Indigo => '#312e81',
            self::Violet => '#4c1d95',
            self::Purple => '#581c87',
            self::Fuchsia => '#701a75',
            self::Pink => '#831843',
        };
    }

    /**
     * Tailwind 600 shade
     */
    public function darkHex(): string
    {
        return match ($this) {
            self::Neutral => '#525252',
            self::Gray => '#4b5563',
            self::Red => '#dc2626',
            self::Orange => '#ea580c',
            self::Yellow => '#ca8a04',
            self::Lime => '#65a30d',
            self::Emerald => '#059669',
            self::Teal => '#0d9488',
            self::Cyan => '#0891b2',
            self::Sky => '#0284c7',
            self::Blue => '#2563eb',
            self::Indigo => '#4f46e5',
            self::Violet => '#7c3aed',
            self::Purple => '#9333ea',
            self::Fuchsia => '#c026d3',
            self::Pink => '#db2777',
        };
    }
}
