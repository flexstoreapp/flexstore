<?php

declare(strict_types=1);

namespace App\Rules;

use App\Address\PostalCodeMatcher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidPostalCodePattern implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('":pattern" is not a valid postal code pattern.', ['pattern' => (string) $value]));

            return;
        }

        $pattern = mb_trim($value);

        if ($pattern === '') {
            $fail(__('":pattern" is not a valid postal code pattern.', ['pattern' => $value]));

            return;
        }

        if (PostalCodeMatcher::isWildcard($pattern)) {
            $prefix = mb_substr($pattern, 0, -1);

            if ($prefix === '' || preg_match('/^[A-Za-z0-9 -]+$/', $prefix) !== 1) {
                $fail(__('":pattern" is not a valid postal code pattern.', ['pattern' => $value]));
            }

            return;
        }

        if (PostalCodeMatcher::isRange($pattern)) {
            [$start, $end] = explode('..', $pattern);

            if ((int) $start > (int) $end) {
                $fail(__('The postal code range ":pattern" must start before it ends.', ['pattern' => $value]));
            }

            return;
        }

        if (preg_match('/^[A-Za-z0-9 -]+$/', $pattern) !== 1) {
            $fail(__('":pattern" is not a valid postal code pattern.', ['pattern' => $value]));
        }
    }
}
