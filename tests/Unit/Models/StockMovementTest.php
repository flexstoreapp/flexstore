<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(StockMovement::class);

uses()->group('models', 'stock');

test('has factory', function () {
    expect(StockMovement::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $movement = StockMovement::factory()->create([
        'reason' => StockMovementReason::Manual,
    ]);

    $casts = $movement->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('reason', StockMovementReason::class);
});

test('has product relationship', function () {
    $product = Product::factory()->create();
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($movement->product)->toBeInstanceOf(Product::class)
        ->and($movement->product->id)->toBe($product->id);
});

test('has product variant relationship', function () {
    $productVariant = ProductVariant::factory()->create();
    $movement = StockMovement::factory()->create([
        'product_variant_id' => $productVariant->id,
    ]);

    expect($movement->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($movement->productVariant->id)->toBe($productVariant->id);
});

test('handles null product variant relationship', function () {
    $movement = StockMovement::factory()->create(['product_variant_id' => null]);

    expect($movement->productVariant)->toBeNull();
});

test('has user relationship', function () {
    $user = User::factory()->create();
    $movement = StockMovement::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($movement->user)->toBeInstanceOf(User::class)
        ->and($movement->user->id)->toBe($user->id);
});

test('handles null user relationship', function () {
    $movement = StockMovement::factory()->create(['user_id' => null]);

    expect($movement->user)->toBeNull();
});

test('has reference morph to relationship', function () {
    $product = Product::factory()->create();
    $movement = StockMovement::factory()->create([
        'reference_type' => Product::class,
        'reference_id' => $product->id,
    ]);

    expect($movement->reference)->toBeInstanceOf(Product::class)
        ->and($movement->reference->id)->toBe($product->id);
});

test('handles null reference relationship', function () {
    $movement = StockMovement::factory()->create([
        'reference_type' => null,
        'reference_id' => null,
    ]);

    expect($movement->reference)->toBeNull();
});

test('casts stock movement reason enum correctly', function () {
    $movement = StockMovement::factory()->create(['reason' => StockMovementReason::Sale]);

    expect($movement->reason)->toBeInstanceOf(StockMovementReason::class);
    expect($movement->reason)->toBe(StockMovementReason::Sale);
});

test('handles all stock movement reason enum values', function () {
    $reasons = [
        StockMovementReason::Manual,
        StockMovementReason::Received,
        StockMovementReason::Damaged,
        StockMovementReason::Lost,
        StockMovementReason::Return,
        StockMovementReason::InventoryCount,
        StockMovementReason::Transfer,
        StockMovementReason::Sale,
        StockMovementReason::Refund,
        StockMovementReason::Other,
    ];

    foreach ($reasons as $reason) {
        $movement = StockMovement::factory()->create(['reason' => $reason]);

        expect($movement->reason)->toBe($reason);
    }
});

test('handles quantity changes correctly', function () {
    $movement = StockMovement::factory()->create([
        'quantity' => 5,
        'quantity_before' => 10,
        'quantity_after' => 15,
    ]);

    expect($movement->quantity)->toBe(5);
    expect($movement->quantity_before)->toBe(10);
    expect($movement->quantity_after)->toBe(15);
});

test('handles negative quantity changes', function () {
    $movement = StockMovement::factory()->create([
        'quantity' => -3,
        'quantity_before' => 8,
        'quantity_after' => 5,
    ]);

    expect($movement->quantity)->toBe(-3);
    expect($movement->quantity_before)->toBe(8);
    expect($movement->quantity_after)->toBe(5);
});

test('handles notes field', function () {
    $movement = StockMovement::factory()->create(['notes' => 'Manual stock adjustment']);

    expect($movement->notes)->toBe('Manual stock adjustment');
});

test('handles null notes field', function () {
    $movement = StockMovement::factory()->create(['notes' => null]);

    expect($movement->notes)->toBeNull();
});
