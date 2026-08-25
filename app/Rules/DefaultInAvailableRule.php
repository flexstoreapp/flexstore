<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class DefaultInAvailableRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly string $availableField,
    ) {
    }

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $available = $this->data[$this->availableField] ?? Setting::getValue($this->availableField, []);

        if (is_array($available) && ! in_array($value, $available, true)) {
            $fail(__('The selected :attribute must be one of the available options.'));
        }
    }
}
