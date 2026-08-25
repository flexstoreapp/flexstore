<?php

declare(strict_types=1);

namespace App\Address;

use App\DTOs\AddressLocation;
use App\Models\Region;

final readonly class RegionAddressMatcher
{
    public static function matches(Region $region, AddressLocation $address): bool
    {
        if (! empty($region->countries) && ! in_array($address->countryCode, $region->countries, true)) {
            return false;
        }

        if (! empty($region->states) && ! in_array($address->state, $region->states, true)) {
            return false;
        }

        return empty($region->postal_codes) || PostalCodeMatcher::matches($address->postalCode, $region->postal_codes);
    }
}
