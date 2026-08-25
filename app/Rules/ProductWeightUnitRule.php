<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\ProductType;
use App\Enums\WeightUnit;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ProductWeightUnitRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array{variants: array<string, mixed>, type: string}
     */
    private array $data;

    /**
     * @param  array{variants: array<string, mixed>, type: string}  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($this->data['variants'] ?? [])) {
            return;
        }

        $isPhysicalProduct = ($this->data['type'] ?? ProductType::Physical->value) === ProductType::Physical->value;

        if ($isPhysicalProduct && is_null($value)) {
            $fail(__('validation.required', ['attribute' => mb_strtolower(__('Weight unit'))]));

            return;
        }

        if ($isPhysicalProduct && ! is_string($value)) {
            $fail(__('validation.string', ['attribute' => mb_strtolower(__('Weight unit'))]));

            return;
        }

        if ($isPhysicalProduct && ! WeightUnit::tryFrom($value)) {
            $fail(__('validation.enum', ['attribute' => mb_strtolower(__('Weight unit'))]));

            return;
        }
    }
}
