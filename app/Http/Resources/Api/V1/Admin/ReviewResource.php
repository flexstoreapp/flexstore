<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Review
 */
final class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $product = $this->resource->relationLoaded('product') ? $this->resource->product : null;
        $user = $this->resource->relationLoaded('user') ? $this->resource->user : null;

        return [
            'id' => $this->resource->id,
            'rating' => $this->resource->rating,
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'status' => $this->resource->status->value,
            'product' => $product instanceof Product ? [
                'id' => $product->id,
                'title' => LocalizedText::resolve($product->title),
                'image' => $product->featured_media instanceof Media
                    ? new MediaResource($product->featured_media)
                    : null,
            ] : null,
            'user' => $user instanceof User ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
