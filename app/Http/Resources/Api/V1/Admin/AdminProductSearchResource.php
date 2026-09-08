<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by ProductSearchQuery.
 */
final class AdminProductSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $product */
        $product = $this->resource;

        /** @var iterable<int, array<string, mixed>>|null $variants */
        $variants = $product['variants'] ?? null;

        return [
            'id' => $product['id'],
            'type' => $product['type'],
            'title' => LocalizedText::resolve($product['title']),
            'price' => $product['price'],
            'price_range' => $product['price_range'],
            'featured_media' => $this->media($product['featured_media'] ?? null),
            ...$variants === null ? [] : ['variants' => $this->variants($variants)],
        ];
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $variants
     * @return list<array<string, mixed>>
     */
    private function variants(iterable $variants): array
    {
        $mapped = [];

        foreach ($variants as $variant) {
            /** @var iterable<int, array<string, mixed>> $options */
            $options = $variant['options'] ?? [];

            $mapped[] = [
                'id' => $variant['id'],
                'title' => LocalizedText::resolve($variant['title']),
                'price' => $variant['price'],
                'media' => $this->media($variant['media'] ?? null),
                'options' => $this->variantOptions($options),
            ];
        }

        return $mapped;
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    private function variantOptions(iterable $options): array
    {
        $mapped = [];

        foreach ($options as $option) {
            $mapped[] = [
                'option_id' => $option['option_id'],
                'value_id' => $option['value_id'],
                'name' => LocalizedText::resolve($option['name']),
                'value' => LocalizedText::resolve($option['value']),
            ];
        }

        return $mapped;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function media(mixed $media): ?array
    {
        if ($media instanceof Media) {
            $media = $media->toArray();
        }

        if (! is_array($media)) {
            return null;
        }

        return [
            'id' => $media['id'] ?? null,
            'url' => $media['url'] ?? null,
            'thumbnail_url' => $media['thumbnail_url'] ?? null,
            'small_thumbnail_url' => $media['small_thumbnail_url'] ?? null,
            'alt' => $media['alt'] ?? null,
        ];
    }
}
