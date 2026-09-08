<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\DTOs\CouponValidationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin CouponValidationResult
 */
final class CouponValidationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'coupon' => new CouponResource($this->resource->coupon),
            'discount' => $this->resource->discount,
        ];
    }
}
