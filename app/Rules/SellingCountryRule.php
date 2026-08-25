<?php

declare(strict_types=1);

namespace App\Rules;

use App\Address\SellingCountries;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SellingCountryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! SellingCountries::supports($value)) {
            $fail(__('The store does not sell to the selected :attribute.'));
        }
    }
}
