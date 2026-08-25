<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidIpList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid list of IP addresses.'));

            return;
        }

        $ips = array_filter(array_map(trim(...), explode("\n", $value)));

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                $fail(__('":ip" is not a valid IP address.', ['ip' => $ip]));

                return;
            }
        }
    }
}
