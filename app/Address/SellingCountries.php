<?php

declare(strict_types=1);

namespace App\Address;

use App\Enums\Country;
use App\Models\Setting;

final class SellingCountries
{
    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        $configured = self::configured();

        return $configured === [] ? array_values(Country::codes()) : $configured;
    }

    public static function supports(string $countryCode): bool
    {
        return in_array($countryCode, self::codes(), true);
    }

    /**
     * @return list<string>
     */
    private static function configured(): array
    {
        $value = Setting::getValue('selling_countries', []);

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $code): bool => is_string($code) && in_array($code, Country::codes(), true),
        ));
    }
}
