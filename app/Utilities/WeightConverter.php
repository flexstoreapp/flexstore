<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\WeightUnit;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class WeightConverter
{
    public static function toGrams(string $weight, WeightUnit $unit): string
    {
        $weightDecimal = BigDecimal::of($weight);

        $result = match ($unit) {
            WeightUnit::Kg => $weightDecimal->multipliedBy('1000'),
            WeightUnit::G => $weightDecimal,
            WeightUnit::Lb => $weightDecimal->multipliedBy('453.592'),
            WeightUnit::Oz => $weightDecimal->multipliedBy('28.3495'),
        };

        return $result->toScale(2, RoundingMode::HalfUp)->toString();
    }

    public static function fromGrams(string $grams, WeightUnit $unit): string
    {
        $gramsDecimal = BigDecimal::of($grams);

        $result = match ($unit) {
            WeightUnit::Kg => $gramsDecimal->dividedBy('1000', 2, RoundingMode::HalfUp),
            WeightUnit::G => $gramsDecimal,
            WeightUnit::Lb => $gramsDecimal->dividedBy('453.592', 2, RoundingMode::HalfUp),
            WeightUnit::Oz => $gramsDecimal->dividedBy('28.3495', 2, RoundingMode::HalfUp),
        };

        return $result->toScale(2, RoundingMode::HalfUp)->toString();
    }
}
