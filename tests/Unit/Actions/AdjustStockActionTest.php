<?php

declare(strict_types=1);

use App\Actions\AdjustStockAction;
use App\DTOs\StockAdjustmentInput;
use App\Enums\StockMovementReason;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\AdminLowStockNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

covers(AdjustStockAction::class, StockAdjustmentInput::class);

uses()->group('actions', 'inventory');

test('adjusts stock for a product', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $action = app(AdjustStockAction::class);
    $movement = $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => 5,
        'reason' => StockMovementReason::Manual,
        'notes' => 'Test adjustment',
    ]));

    expect($product->fresh()->stock)->toBe(15)
        ->and($product->fresh()->in_stock)->toBeTrue()
        ->and($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->quantity)->toBe(5)
        ->and($movement->quantity_before)->toBe(10)
        ->and($movement->quantity_after)->toBe(15)
        ->and($movement->reason)->toBe(StockMovementReason::Manual)
        ->and($movement->user_id)->toBe($user->id);
});

test('decreases stock for a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $action = app(AdjustStockAction::class);
    $movement = $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -3,
        'reason' => StockMovementReason::Sale,
    ]));

    expect($product->fresh()->stock)->toBe(7)
        ->and($product->fresh()->in_stock)->toBeTrue()
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->quantity_before)->toBe(10)
        ->and($movement->quantity_after)->toBe(7);
});

test('sets in_stock to false when stock reaches zero', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
    ]);

    $action = app(AdjustStockAction::class);
    $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -5,
        'reason' => StockMovementReason::Sale,
    ]));

    expect($product->fresh()->stock)->toBe(0)
        ->and($product->fresh()->in_stock)->toBeFalse();
});

test('adjusts stock for a product variant', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 10,
    ]);

    $action = app(AdjustStockAction::class);
    $movement = $action->handle($user, $product, $variant, StockAdjustmentInput::fromArray([
        'quantity' => 5,
        'reason' => StockMovementReason::Received,
    ]));

    expect($variant->fresh()->stock)->toBe(15)
        ->and($movement->product_variant_id)->toBe($variant->id);
});

test('throws exception when adjusting stock for product that does not track quantity', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => false,
        'stock' => null,
    ]);

    $action = app(AdjustStockAction::class);

    expect(fn () => $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => 5,
        'reason' => StockMovementReason::Manual,
    ])))->toThrow(InvalidArgumentException::class);
});

test('prevents negative stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
    ]);

    $action = app(AdjustStockAction::class);

    expect(fn () => $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -10,
        'reason' => StockMovementReason::Sale,
    ])))->toThrow(InsufficientStockException::class);
});

test('allows negative stock for sales when allowOversell is true', function () {
    Log::spy();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 2,
    ]);

    $action = app(AdjustStockAction::class);
    $movement = $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -5,
        'reason' => StockMovementReason::Sale,
    ]), allowOversell: true);

    expect($product->fresh()->stock)->toBe(-3)
        ->and($product->fresh()->in_stock)->toBeFalse()
        ->and($movement->quantity_before)->toBe(2)
        ->and($movement->quantity_after)->toBe(-3);

    Log::shouldHaveReceived('warning')->once()->with('Stock oversold for product.', Mockery::on(
        fn (array $context) => $context['product_id'] === $product->id && $context['quantity_after'] === -3
    ));
});

test('dispatches low stock notification when stock transitions to low', function () {
    Notification::fake();
    Setting::setValue('notification_admin_low_stock', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 6,
        'low_stock_threshold' => 5,
    ]);

    $action = app(AdjustStockAction::class);
    $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -1,
        'reason' => StockMovementReason::Manual,
    ]));

    Notification::assertSentOnDemand(AdminLowStockNotification::class);
});

test('does not dispatch low stock notification when stock is already low', function () {
    Notification::fake();
    Setting::setValue('notification_admin_low_stock', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    $action = app(AdjustStockAction::class);
    $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -1,
        'reason' => StockMovementReason::Manual,
    ]));

    Notification::assertNothingSent();
});

test('does not dispatch low stock notification when stock is above threshold', function () {
    Notification::fake();
    Setting::setValue('notification_admin_low_stock', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 100,
        'low_stock_threshold' => 5,
    ]);

    $action = app(AdjustStockAction::class);
    $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => -1,
        'reason' => StockMovementReason::Manual,
    ]));

    Notification::assertNothingSent();
});

test('does not dispatch low stock notification on stock increase', function () {
    Notification::fake();
    Setting::setValue('notification_admin_low_stock', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    $action = app(AdjustStockAction::class);
    $action->handle($user, $product, null, StockAdjustmentInput::fromArray([
        'quantity' => 2,
        'reason' => StockMovementReason::Received,
    ]));

    Notification::assertNothingSent();
});
