<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;
use Symfony\Component\HttpFoundation\Response;

/**
 * @mixin Cart
 */
final class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->id,
            'items' => CartItemResource::collection($this->items),
            'item_count' => $this->items->sum('quantity'),
            'coupon_code' => $this->coupon_code,
            'requires_shipping' => $this->requiresShipping(),
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
        ];
    }

    /**
     * A cart is created implicitly on first use, which would otherwise make the
     * status code depend on whether the caller already had a cart token.
     */
    #[Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(Response::HTTP_OK);
    }
}
