<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ProductOptionInput;
use App\DTOs\ProductOptionValueInput;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class UpsertProductOptionsAction
{
    /**
     * @param  list<ProductOptionInput>  $options
     * @return Collection<int, ProductOption>
     */
    public function handle(Product $product, array $options): Collection
    {
        return DB::transaction(function () use ($product, $options): Collection {
            if ($options === []) {
                $product->options()->delete();

                return collect();
            }

            $optionIds = $this->processOptions($product, $options);
            $this->cleanupOrphanedOptions($product, $optionIds);

            return $product->options()->with('values')->whereIn('id', $optionIds)->get();
        });
    }

    /**
     * @param  list<ProductOptionInput>  $options
     * @return list<string>
     */
    private function processOptions(Product $product, array $options): array
    {
        $optionIds = [];

        foreach ($options as $optionInput) {
            $option = $this->upsertOption($product, $optionInput);

            $this->syncOptionValues($option, $optionInput->values);

            $optionIds[] = $option->id;
        }

        return $optionIds;
    }

    /**
     * @param  list<string>  $optionIds
     */
    private function cleanupOrphanedOptions(Product $product, array $optionIds): void
    {
        if ($optionIds !== []) {
            $product->options()
                ->whereNotIn('id', $optionIds)
                ->delete();
        }
    }

    private function upsertOption(Product $product, ProductOptionInput $option): ProductOption
    {
        return $product->options()->updateOrCreate(
            ['id' => $option->id],
            $option->name !== null ? ['name' => $option->name] : [],
        );
    }

    /**
     * @param  list<ProductOptionValueInput>  $values
     */
    private function syncOptionValues(ProductOption $option, array $values): void
    {
        $valueIds = array_map(fn (ProductOptionValueInput $v): string => $v->id, $values);
        $existingValues = $option->values()->whereIn('id', $valueIds)->get()->keyBy('id');
        $locale = app()->getLocale();
        $now = now();

        $valuesToUpsert = [];

        foreach ($values as $valueInput) {
            $existingTranslations = $existingValues->get($valueInput->id)?->getTranslations('value') ?? [];
            $mergedTranslations = array_merge($existingTranslations, [$locale => $valueInput->value]);

            $valuesToUpsert[] = [
                'id' => $valueInput->id,
                'product_option_id' => $option->id,
                'value' => json_encode($mergedTranslations),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($valuesToUpsert !== []) {
            $option->values()->upsert($valuesToUpsert, uniqueBy: 'id', update: ['value', 'updated_at']);
        }

        $option->values()->whereNotIn('id', $valueIds)->delete();
    }
}
