<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class LocalRedirectPath implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! is_string($value)
            || ! str_starts_with($value, '/')
            || str_starts_with($value, '//')
            || str_contains($value, '://')
            || str_contains($value, '\\')
        ) {
            $fail(__('The :attribute must be a valid local path.'));
        }
    }
}
