<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Actions\SyncMediaAction;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTaxDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(OrderItem::class);

uses()->group('models', 'orders');

test('has factory', function () {
    expect(OrderItem::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasTranslations trait', function () {
    expect(OrderItem::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $orderItem = new OrderItem();

    expect($orderItem->getTranslatableAttributes())->toContain('product_title');
});

test('touches order relationship', function () {
    $orderItem = new OrderItem();

    expect($orderItem->getTouchedRelations())->toBe(['order']);
});

test('belongs to order relationship', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

    expect($orderItem->order)->toBeInstanceOf(Order::class)
        ->and($orderItem->order->id)->toBe($order->id);
});

test('belongs to product relationship', function () {
    $product = Product::factory()->create();
    $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

    expect($orderItem->product)->toBeInstanceOf(Product::class)
        ->and($orderItem->product->id)->toBe($product->id);
});

test('belongs to product variant relationship', function () {
    $product = Product::factory()->create();
    $productVariant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $orderItem = OrderItem::factory()->create(['product_variant_id' => $productVariant->id]);

    expect($orderItem->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($orderItem->productVariant->id)->toBe($productVariant->id);
});

test('has many tax details relationship', function () {
    $orderItem = OrderItem::factory()->create();
    $taxDetails = OrderTaxDetail::factory()->count(2)->create(['order_item_id' => $orderItem->id]);

    expect($orderItem->taxDetails)->toHaveCount(2)
        ->and($orderItem->taxDetails->first())->toBeInstanceOf(OrderTaxDetail::class);
});

test('handles null product relationship', function () {
    $orderItem = OrderItem::factory()->create(['product_id' => null]);

    expect($orderItem->product)->toBeNull();
});

test('handles null product variant relationship', function () {
    $orderItem = OrderItem::factory()->create(['product_variant_id' => null]);

    expect($orderItem->productVariant)->toBeNull();
});

test('handles null weight', function () {
    $orderItem = OrderItem::factory()->create(['weight' => null]);

    expect($orderItem->weight)->toBeNull();
});

test('handles null variant options', function () {
    $orderItem = OrderItem::factory()->create(['variant_options' => null]);

    expect($orderItem->variant_options)->toBeNull();
});

test('handles complex variant options', function () {
    $options = [
        'color' => 'blue',
        'size' => 'medium',
        'material' => 'cotton',
        'style' => 'casual',
    ];

    $orderItem = OrderItem::factory()->create(['variant_options' => $options]);

    expect($orderItem->variant_options)->toBe($options);
});

test('handles translatable product title with multiple locales', function () {
    $orderItem = OrderItem::factory()->create([
        'product_title' => [
            'en' => 'English Title',
            'es' => 'Spanish Title',
            'fr' => 'French Title',
        ],
    ]);

    app()->setLocale('en');
    expect($orderItem->product_title)->toBe('English Title');

    app()->setLocale('es');
    expect($orderItem->product_title)->toBe('Spanish Title');

    app()->setLocale('fr');
    expect($orderItem->product_title)->toBe('French Title');
});

test('handles empty tax details collection', function () {
    $orderItem = OrderItem::factory()->create();

    expect($orderItem->taxDetails)->toHaveCount(0);
    expect($orderItem->taxDetails)->toBeInstanceOf(Collection::class);
});

test('belongs to media relationship', function () {
    $media = Media::factory()->uploaded()->create();
    $orderItem = OrderItem::factory()->create(['media_id' => $media->id]);

    expect($orderItem->media)->toBeInstanceOf(Media::class)
        ->and($orderItem->media->id)->toBe($media->id);
});

test('media returns the snapshot, ignoring the live product image', function () {
    $snapshot = Media::factory()->uploaded()->create();
    $current = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$current->id]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'media_id' => $snapshot->id,
    ]);
    $orderItem->load('product.mediaGallery');

    expect($orderItem->media)->toBeInstanceOf(Media::class)
        ->and($orderItem->media->id)->toBe($snapshot->id);
});

test('media keeps the snapshot after the product is deleted', function () {
    $snapshot = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'media_id' => $snapshot->id,
    ]);

    $product->delete();
    $orderItem->refresh();

    expect($orderItem->product)->toBeNull()
        ->and($orderItem->media?->id)->toBe($snapshot->id);
});

test('media does not fall back to the live product image when not snapshotted', function () {
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'media_id' => null,
    ]);
    $orderItem->load('product.mediaGallery');

    expect($orderItem->media)->toBeNull();
});

test('media returns null when nothing is snapshotted', function () {
    $orderItem = OrderItem::factory()->create([
        'product_id' => null,
        'product_variant_id' => null,
        'media_id' => null,
    ]);

    expect($orderItem->media)->toBeNull();
});
