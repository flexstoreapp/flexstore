<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StockAdjustmentInput;
use App\Enums\StockMovementReason;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\AdminLowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

final readonly class AdjustStockAction
{
    public function __construct(
        private SendAdminNotificationAction $sendAdminNotificationAction,
    ) {
    }

    public function handle(?User $user, Product $product, ?ProductVariant $variant, StockAdjustmentInput $input, bool $allowOversell = false): StockMovement
    {
        $stockMovement = DB::transaction(function () use ($user, $product, $variant, $input, $allowOversell): StockMovement {
            if (! $variant instanceof ProductVariant && $product->variants()->exists()) {
                throw new InvalidArgumentException('Cannot adjust stock for base product when variants exist. Stock must be managed at the variant level.');
            }

            $target = $variant ?? $product;

            /** @var Product|ProductVariant $target */
            $target = $target->newQuery()->lockForUpdate()->findOrFail($target->getKey());

            if (! $target->track_stock) {
                throw new InvalidArgumentException('Cannot adjust stock for product/variant that does not track stock.');
            }

            $quantityBefore = $target->stock ?? 0;
            $quantityAfter = $quantityBefore + $input->quantity;

            if ($quantityAfter < 0) {
                $quantityAfter = $this->handleNegativeStock(
                    $product, $variant, $input->reason, $quantityBefore, $quantityAfter, $allowOversell,
                );
            }

            $target->update([
                'stock' => $quantityAfter,
                'in_stock' => $quantityAfter > 0,
            ]);

            return StockMovement::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'user_id' => $user?->id,
                'quantity' => $input->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $input->reason,
                'reference_type' => $input->referenceType,
                'reference_id' => $input->referenceId,
                'notes' => $input->notes,
            ]);
        });

        $target = $variant ?? $product;

        $becameLowStock = $input->quantity < 0
            && ! $this->wasLowStock($stockMovement, $target)
            && $this->isNowLowStock($stockMovement, $target);

        if ($becameLowStock && Setting::getValue('notification_admin_low_stock')) {
            $this->sendAdminNotificationAction->handle(new AdminLowStockNotification($product, $variant));
        }

        return $stockMovement;
    }

    private function wasLowStock(StockMovement $movement, Product|ProductVariant $target): bool
    {
        $threshold = $target->low_stock_threshold ?? (int) Setting::getValue('default_low_stock_threshold', 10);

        return $movement->quantity_before <= $threshold;
    }

    private function isNowLowStock(StockMovement $movement, Product|ProductVariant $target): bool
    {
        $threshold = $target->low_stock_threshold ?? (int) Setting::getValue('default_low_stock_threshold', 10);

        return $movement->quantity_after <= $threshold;
    }

    private function handleNegativeStock(
        Product $product,
        ?ProductVariant $variant,
        StockMovementReason $reason,
        int $quantityBefore,
        int $quantityAfter,
        bool $allowOversell,
    ): int {
        if ($reason === StockMovementReason::Sale && ! $allowOversell) {
            throw new InsufficientStockException(
                product: $product,
                variant: $variant,
                requested: abs($quantityAfter - $quantityBefore),
                available: $quantityBefore,
            );
        }

        if ($reason === StockMovementReason::Sale) {
            Log::warning('Stock oversold for product.', [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
            ]);

            return $quantityAfter;
        }

        report(new RuntimeException(
            "Stock for product {$product->id}" . ($variant instanceof ProductVariant ? " variant {$variant->id}" : '') .
            " would go negative ({$quantityAfter}), clamped to 0. Reason: {$reason->value}"
        ));

        return 0;
    }
}
