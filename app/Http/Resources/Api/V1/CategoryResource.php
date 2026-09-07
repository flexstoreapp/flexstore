<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by StorefrontCategoryListQuery.
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $category */
        $category = $this->resource;

        /** @var list<array<string, mixed>> $children */
        $children = $category['children'] ?? [];

        return [
            'id' => $category['id'],
            'url_handle' => $category['url_handle'],
            'name' => LocalizedText::resolve($category['name']),
            'product_count' => $category['product_count'],
            'children' => self::collection($children),
        ];
    }
}
