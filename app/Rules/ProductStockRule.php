<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\ProductType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ProductStockRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array{variants: array<string, mixed>, track_stock: bool|string, type?: string}
     */
    private array $data;

    /**
     * @param  array{variants: array<string, mixed>, track_stock: bool|string, type?: string}  $data
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

        if (($this->data['type'] ?? ProductType::Physical->value) === ProductType::Digital->value) {
            return;
        }

        $trackingStock = filter_var($this->data['track_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($trackingStock && is_null($value)) {
            $fail(__('validation.required', ['attribute' => mb_strtolower(__('Stock'))]));

            return;
        }

        if ($trackingStock && ! is_numeric($value)) {
            $fail(__('validation.numeric', ['attribute' => mb_strtolower(__('Stock'))]));

            return;
        }

        if ($trackingStock && $value < 0) {
            $fail(__('validation.min.numeric', ['attribute' => mb_strtolower(__('Stock')), 'min' => 0]));

            return;
        }
    }
}
