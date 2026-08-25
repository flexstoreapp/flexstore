<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\DimensionUnit;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class DimensionConverter
{
    public static function toCentimeters(string $value, DimensionUnit $unit): string
    {
        $decimal = BigDecimal::of($value);

        $result = match ($unit) {
            DimensionUnit::Cm => $decimal,
            DimensionUnit::Mm => $decimal->multipliedBy('0.1'),
            DimensionUnit::In => $decimal->multipliedBy('2.54'),
        };

        return $result->toScale(2, RoundingMode::HalfUp)->toString();
    }
}
