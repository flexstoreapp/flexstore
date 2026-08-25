<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case Physical = 'physical';
    case Digital = 'digital';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Physical => __('Physical'),
            self::Digital => __('Digital'),
        };
    }

    public function requiresShipping(): bool
    {
        return $this === self::Physical;
    }
}
