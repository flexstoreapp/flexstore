<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

final class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->with('variants')->get();
        $adminUsers = User::query()
            ->whereRelation('roles', 'name', Role::SuperAdmin)
            ->get();

        foreach ($products as $product) {
            $this->createInitialStockMovement($product, $adminUsers);
            $this->createRandomStockMovements($product, $adminUsers);
        }
    }

    /**
     * @param  Collection<int, User>  $adminUsers
     */
    private function createInitialStockMovement(Product $product, Collection $adminUsers): void
    {
        $user = $adminUsers->isNotEmpty() ? $adminUsers->random() : null;
        $productStock = $product->stock ?? 0;

        StockMovement::factory()
            ->forProduct($product)
            ->received()
            ->create([
                'user_id' => $user?->id,
                'quantity' => $productStock,
                'quantity_before' => 0,
                'quantity_after' => $productStock,
                'notes' => 'Initial stock received',
                'created_at' => $product->created_at,
                'updated_at' => $product->created_at,
            ]);

        foreach ($product->variants as $variant) {
            $variantStock = $variant->stock ?? 0;

            StockMovement::factory()
                ->forVariant($variant)
                ->received()
                ->create([
                    'user_id' => $user?->id,
                    'quantity' => $variantStock,
                    'quantity_before' => 0,
                    'quantity_after' => $variantStock,
                    'notes' => 'Initial variant stock received',
                    'created_at' => $product->created_at,
                    'updated_at' => $product->created_at,
                ]);
        }
    }

    /**
     * @param  Collection<int, User>  $adminUsers
     */
    private function createRandomStockMovements(Product $product, Collection $adminUsers): void
    {
        $movementCount = fake()->numberBetween(2, 6);

        for ($i = 0; $i < $movementCount; $i++) {
            $user = $adminUsers->isNotEmpty() ? $adminUsers->random() : null;
            $reason = fake()->randomElement([
                StockMovementReason::Manual,
                StockMovementReason::Received,
                StockMovementReason::Damaged,
                StockMovementReason::Lost,
                StockMovementReason::InventoryCount,
                StockMovementReason::Transfer,
            ]);

            $quantityBefore = fake()->numberBetween(10, 100);
            $quantity = $this->getQuantityForReason($reason);
            $quantityAfter = max(0, $quantityBefore + $quantity);

            StockMovement::factory()
                ->forProduct($product)
                ->withReason($reason)
                ->create([
                    'user_id' => $user?->id,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'notes' => $this->getNotesForReason($reason),
                    'created_at' => fake()->dateTimeBetween($product->created_at, 'now'),
                ]);
        }
    }

    private function getQuantityForReason(StockMovementReason $reason): int
    {
        return match ($reason) {
            StockMovementReason::Received => fake()->numberBetween(10, 50),
            StockMovementReason::Damaged => fake()->numberBetween(-5, -1),
            StockMovementReason::Lost => fake()->numberBetween(-3, -1),
            StockMovementReason::Sale => fake()->numberBetween(-10, -1),
            StockMovementReason::Refund => fake()->numberBetween(1, 5),
            StockMovementReason::InventoryCount => fake()->numberBetween(-10, 10),
            StockMovementReason::Transfer => fake()->numberBetween(-20, 20),
            default => fake()->numberBetween(-5, 15),
        };
    }

    private function getNotesForReason(StockMovementReason $reason): ?string
    {
        $notes = match ($reason) {
            StockMovementReason::Received => [
                'Shipment received from supplier',
                'Restocked from warehouse',
                'New inventory batch arrived',
            ],
            StockMovementReason::Damaged => [
                'Items damaged during handling',
                'Water damage in storage',
                'Defective items removed',
            ],
            StockMovementReason::Lost => [
                'Items missing after inventory check',
                'Unaccounted shrinkage',
                'Lost in transit',
            ],
            StockMovementReason::InventoryCount => [
                'Quarterly inventory audit',
                'Stock reconciliation',
                'Physical count adjustment',
            ],
            StockMovementReason::Transfer => [
                'Transferred to another location',
                'Moved to fulfillment center',
                'Inter-warehouse transfer',
            ],
            StockMovementReason::Manual => [
                'Manual stock adjustment',
                'Correction entry',
                'Admin adjustment',
            ],
            default => null,
        };

        if ($notes === null) {
            return null;
        }

        return fake()->randomElement($notes);
    }
}
