<?php

declare(strict_types=1);

namespace App\Enums;

enum StorefrontAccent: string
{
    case Blue = 'blue';
    case Green = 'green';
    case Rose = 'rose';
    case Gold = 'gold';
    case Teal = 'teal';
    case Magenta = 'magenta';
    case Brown = 'brown';
    case Orange = 'orange';
    case Red = 'red';
    case Pink = 'pink';
    case Terracotta = 'terracotta';
    case Violet = 'violet';

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Blue;
    }

    public static function hexFor(?string $value): string
    {
        return self::fromValue($value)->hex();
    }

    public static function darkHexFor(?string $value): string
    {
        return self::fromValue($value)->darkHex();
    }

    /**
     * Accent tokens for the Northmart design system
     * (`--color-primary`, `--color-primary-hover`, `--color-primary-tint`).
     *
     * @return array{primary: string, hover: string, tint: string}
     */
    public function tokens(): array
    {
        [$primary, $hover, $tint] = match ($this) {
            self::Blue => ['oklch(0.48 0.16 250)', 'oklch(0.41 0.15 250)', 'oklch(0.95 0.03 250)'],
            self::Green => ['oklch(0.53 0.14 150)', 'oklch(0.46 0.13 150)', 'oklch(0.95 0.04 150)'],
            self::Rose => ['oklch(0.62 0.1 20)', 'oklch(0.55 0.09 20)', 'oklch(0.96 0.03 20)'],
            self::Gold => ['oklch(0.6 0.11 85)', 'oklch(0.52 0.1 85)', 'oklch(0.96 0.04 85)'],
            self::Teal => ['oklch(0.54 0.11 185)', 'oklch(0.47 0.1 185)', 'oklch(0.95 0.03 185)'],
            self::Magenta => ['oklch(0.48 0.17 325)', 'oklch(0.41 0.16 325)', 'oklch(0.95 0.04 325)'],
            self::Brown => ['oklch(0.5 0.09 55)', 'oklch(0.43 0.08 55)', 'oklch(0.95 0.03 55)'],
            self::Orange => ['oklch(0.62 0.16 45)', 'oklch(0.54 0.15 45)', 'oklch(0.96 0.04 45)'],
            self::Red => ['oklch(0.52 0.19 25)', 'oklch(0.45 0.18 25)', 'oklch(0.95 0.04 25)'],
            self::Pink => ['oklch(0.55 0.17 350)', 'oklch(0.48 0.16 350)', 'oklch(0.96 0.04 350)'],
            self::Terracotta => ['oklch(0.55 0.12 40)', 'oklch(0.48 0.11 40)', 'oklch(0.96 0.04 40)'],
            self::Violet => ['oklch(0.52 0.15 300)', 'oklch(0.45 0.14 300)', 'oklch(0.95 0.04 300)'],
        };

        return ['primary' => $primary, 'hover' => $hover, 'tint' => $tint];
    }

    public function hex(): string
    {
        return match ($this) {
            self::Blue => '#005eb3',
            self::Green => '#11813c',
            self::Rose => '#bb6d6d',
            self::Gold => '#9e7a23',
            self::Teal => '#008377',
            self::Magenta => '#8a3091',
            self::Brown => '#8a542d',
            self::Orange => '#d16022',
            self::Red => '#be222a',
            self::Pink => '#b53c7f',
            self::Terracotta => '#ab5637',
            self::Violet => '#7750b1',
        };
    }

    public function darkHex(): string
    {
        return match ($this) {
            self::Blue => '#1a83db',
            self::Green => '#43a65f',
            self::Rose => '#dc8b8b',
            self::Gold => '#c49f4d',
            self::Teal => '#20a89b',
            self::Magenta => '#b156b7',
            self::Brown => '#b07750',
            self::Orange => '#f47f46',
            self::Red => '#e9504d',
            self::Pink => '#df63a4',
            self::Terracotta => '#d37a5b',
            self::Violet => '#9b74d9',
        };
    }
}
