<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StockAdjustmentInput;
use App\Enums\StockMovementReason;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class RestockOrderItemsAction
{
    public function __construct(
        private AdjustStockAction $adjustStockAction,
    ) {
    }

    /**
     * @param  Collection<int, array{item: OrderItem, quantity: int}>  $entries
     */
    public function handle(
        User $user,
        Collection $entries,
        StockMovementReason $reason,
        string $referenceType,
        int|string $referenceId,
    ): void {
        foreach ($entries as $entry) {
            $orderItem = $entry['item'];
            $product = $orderItem->product;
            $variant = $orderItem->productVariant;

            if (! $product) {
                continue;
            }

            $target = $variant ?? $product;

            if (! $target->track_stock) {
                continue;
            }

            $this->adjustStockAction->handle($user, $product, $variant, StockAdjustmentInput::fromArray([
                'quantity' => $entry['quantity'],
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]));
        }
    }
}
