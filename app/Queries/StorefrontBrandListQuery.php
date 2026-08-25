<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Brand;
use App\Models\Media;
use App\Utilities\TranslatableJsonExtract;

final readonly class StorefrontBrandListQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        return Brand::query()
            ->select(['id', 'name', 'url_handle', 'image_id'])
            ->where('is_active', true)
            ->with(['image:' . Media::displaySelect()])
            ->orderByRaw(...TranslatableJsonExtract::orderByExpression('name'))
            ->get()
            ->map(fn (Brand $brand): array => [
                'id' => $brand->id,
                'name' => $brand->getTranslations('name'),
                'url_handle' => $brand->url_handle,
                'image' => $brand->image,
            ])
            ->all();
    }
}
