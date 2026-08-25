<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Address\AddressFieldRules;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait ResolvesStateName
{
    /**
     * @return Attribute<string|null, never>
     */
    protected function stateName(): Attribute
    {
        return Attribute::get(function (): ?string {
            $attributes = $this->getAttributes();

            return AddressFieldRules::stateName(
                isset($attributes['country_code']) ? (string) $attributes['country_code'] : null,
                isset($attributes['state']) ? (string) $attributes['state'] : null,
            );
        });
    }
}
