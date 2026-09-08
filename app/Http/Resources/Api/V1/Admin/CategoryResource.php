<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Category;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => LocalizedText::resolve($this->resource->name),
            'url_handle' => $this->resource->url_handle,
            'description' => LocalizedText::resolve($this->resource->description),
            'seo_title' => LocalizedText::resolve($this->resource->seo_title),
            'seo_description' => LocalizedText::resolve($this->resource->seo_description),
            'parent_id' => $this->resource->parent_id,
            'sort_order' => $this->resource->sort_order,
            'is_active' => $this->resource->is_active,
        ];
    }
}
