<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Coupon
 */
final class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'type' => $this->resource->type->value,
            'value' => $this->resource->value,
            'min_order_value' => $this->resource->min_order_value,
            'maximum_discount' => $this->resource->maximum_discount,
            'usage_limit' => $this->resource->usage_limit,
            'usage_limit_per_customer' => $this->resource->usage_limit_per_customer,
            'used_count' => $this->resource->used_count,
            'is_active' => $this->resource->is_active,
            'starts_at' => $this->resource->starts_at?->toIso8601String(),
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
