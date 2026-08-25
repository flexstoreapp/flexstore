<?php

declare(strict_types=1);

namespace App\Rules;

use App\Utilities\Translations;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidLocale implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Translations::hasLocale($value)) {
            $fail(__('The selected :attribute is invalid.'));
        }
    }
}
