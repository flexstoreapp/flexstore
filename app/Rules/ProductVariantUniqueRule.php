<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Product;
use App\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ProductVariantUniqueRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array{variants: array<array{id: string, name: string, product_id?: int|null, values: array<array{id: string, name: string}>}>}
     */
    private array $data;

    public function __construct(
        private readonly string $attributeToCheck,
        private readonly string $attributeLabel
    ) {
    }

    /**
     * @param  array{variants: array<array{id: string, name: string, product_id?: int|null, values: array<array{id: string, name: string}>}>}  $data
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
        $index = explode('.', $attribute)[1];
        $variantData = $this->data['variants'][$index] ?? [];
        $variantId = $variantData['id'] ?? null;
        $productId = $variantData['product_id'] ?? null;

        $existsInProducts = Product::query()
            ->when($productId, fn (Builder $query): Builder => $query->whereKeyNot($productId))
            ->where($this->attributeToCheck, $value)
            ->exists();

        $variantUuid = is_string($variantId) && Str::isUuid($variantId) ? $variantId : null;

        $existsInVariants = ProductVariant::query()
            ->when($variantUuid, fn (Builder $query): Builder => $query->whereKeyNot($variantUuid))
            ->when($productId, fn (Builder $query): Builder => $query->where('product_id', $productId))
            ->where($this->attributeToCheck, $value)
            ->exists();

        $existsInOtherVariants = collect($this->data['variants'] ?? [])
            ->filter(fn (array $variant): bool => $variant['id'] !== $variantId)
            ->contains($this->attributeToCheck, $value);

        if ($existsInProducts || $existsInVariants || $existsInOtherVariants) {
            $fail(__('validation.unique', ['attribute' => $this->attributeLabel]));
        }
    }
}
