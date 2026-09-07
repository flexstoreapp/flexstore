<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Review;
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
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->user?->name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
