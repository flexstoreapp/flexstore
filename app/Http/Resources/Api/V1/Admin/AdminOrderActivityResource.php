<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\OrderActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin OrderActivity
 */
final class AdminOrderActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type->value,
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
