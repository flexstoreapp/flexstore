<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;

final readonly class StoreOrderActivityAction
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        Order $order,
        OrderActivityType $type,
        ?User $user = null,
        ?string $comment = null,
        ?array $metadata = null,
    ): OrderActivity {
        return OrderActivity::query()->create([
            'order_id' => $order->id,
            'user_id' => $user?->id,
            'type' => $type,
            'comment' => $comment,
            'metadata' => $metadata,
        ]);
    }
}
