<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CheckoutSession;
use App\Models\StockReservation;
use Carbon\CarbonInterface;

final readonly class ReserveStockAction
{
    /**
     * @param  list<array{product_id: int, product_variant_id?: string|null, quantity: int}>  $items
     */
    public function handle(CheckoutSession $session, array $items, CarbonInterface $expiresAt): void
    {
        $productIds = array_unique(array_column($items, 'product_id'));

        StockReservation::query()
            ->where('expires_at', '<=', now())
            ->whereIn('product_id', $productIds)
            ->delete();

        $reservations = [];
        $now = now();

        foreach ($items as $item) {
            if ($item['quantity'] <= 0) {
                continue;
            }

            $reservations[] = [
                'checkout_session_id' => $session->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ];
        }

        if ($reservations !== []) {
            StockReservation::query()->insert($reservations);
        }
    }
}
