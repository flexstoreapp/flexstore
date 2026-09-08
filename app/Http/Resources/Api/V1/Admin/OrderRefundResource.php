<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin OrderRefund
 */
final class OrderRefundResource extends JsonResource
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
            'status' => $this->status->value,
            'amount' => $this->amount,
            'is_manual_total' => $this->is_manual_total,
            'reason' => $this->reason,
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->items->map(fn (OrderRefundItem $item): array => [
                'id' => $item->id,
                'type' => $item->type->value,
                'order_item_id' => $item->order_item_id,
                'quantity' => $item->quantity,
                'amount' => $item->amount,
                'restock' => $item->restock,
            ])->values()->all(),
        ];
    }
}
