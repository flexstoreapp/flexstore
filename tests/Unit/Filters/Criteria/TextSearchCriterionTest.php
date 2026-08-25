<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\TextSearchCriterion;
use App\Models\Category;
use App\Models\Product;

covers(TextSearchCriterion::class);

uses()->group('filters');

test('applies search across multiple columns', function () {
    Product::factory()->create(['title' => 'Test Product', 'sku' => 'PROD-001']);
    Product::factory()->create(['title' => 'Another Item', 'sku' => 'TEST-002']);
    Product::factory()->create(['title' => 'Different Product', 'sku' => 'DIFF-003']);

    $criterion = new TextSearchCriterion(['title', 'sku']);
    $builder = Product::query();

    $result = $criterion->apply($builder, 'test');

    expect($result->count())->toBe(2);

    $titles = $result->pluck('title')->all();
    expect($titles)->toContain('Test Product')
        ->and($titles)->toContain('Another Item');
});

test('is case insensitive', function () {
    Product::factory()->create(['title' => 'Test Product']);
    Product::factory()->create(['title' => 'Another Item']);

    $criterion = new TextSearchCriterion(['title']);
    $builder = Product::query();

    $result = $criterion->apply($builder, 'TEST');

    expect($result->count())->toBe(1)
        ->and($result->first()->title)->toBe('Test Product');
});

test('handles partial matches', function () {
    Product::factory()->create(['title' => 'Smartphone']);
    Product::factory()->create(['title' => 'Smart TV']);
    Product::factory()->create(['title' => 'Laptop']);

    $criterion = new TextSearchCriterion(['title']);
    $builder = Product::query();

    $result = $criterion->apply($builder, 'Smart');

    expect($result->count())->toBe(2);

    $titles = $result->pluck('title')->all();
    expect($titles)->toContain('Smartphone')
        ->and($titles)->toContain('Smart TV');
});

test('returns empty results when no matches found', function () {
    Product::factory()->create(['title' => 'Test Product']);

    $criterion = new TextSearchCriterion(['title']);
    $builder = Product::query();

    $result = $criterion->apply($builder, 'nonexistent');

    expect($result->count())->toBe(0);
});

test('canApply returns true for non-empty strings', function () {
    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->canApply('test'))->toBeTrue()
        ->and($criterion->canApply('a'))->toBeTrue()
        ->and($criterion->canApply('123'))->toBeTrue();
});

test('canApply returns false for empty strings', function () {
    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply('   '))->toBeFalse();
});

test('canApply returns false for non-string values', function () {
    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(123))->toBeFalse()
        ->and($criterion->canApply([]))->toBeFalse()
        ->and($criterion->canApply(true))->toBeFalse();
});

test('canApply returns true for non-empty token groups', function () {
    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->canApply([['sneakers', 'trainers']]))->toBeTrue()
        ->and($criterion->canApply([['red'], ['shirt']]))->toBeTrue();
});

test('constructor accepts array of columns', function () {
    $columns = ['title', 'description', 'category.name'];
    $criterion = new TextSearchCriterion($columns);

    expect($criterion)->toBeInstanceOf(TextSearchCriterion::class);
});

test('matches all tokens of a multi-word query in any order', function () {
    Product::factory()->create(['title' => 'Cotton Premium Shirt']);
    Product::factory()->create(['title' => 'Cotton Towel']);
    Product::factory()->create(['title' => 'Linen Shirt']);

    $criterion = new TextSearchCriterion(['title']);

    $result = $criterion->apply(Product::query(), 'cotton shirt');

    expect($result->pluck('title')->all())->toBe(['Cotton Premium Shirt']);
});

test('tokens can match across different columns', function () {
    Product::factory()->create(['title' => 'Blue Shirt', 'sku' => 'CTN-01']);
    Product::factory()->create(['title' => 'Blue Mug', 'sku' => 'CRM-02']);

    $criterion = new TextSearchCriterion(['title', 'sku']);

    $result = $criterion->apply(Product::query(), 'blue ctn');

    expect($result->pluck('sku')->all())->toBe(['CTN-01']);
});

test('like wildcards in the query are treated literally', function () {
    Product::factory()->create(['title' => '100% Cotton Tee']);
    Product::factory()->create(['title' => 'Cotton Tee']);

    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->apply(Product::query(), '100%')->pluck('title')->all())->toBe(['100% Cotton Tee'])
        ->and($criterion->apply(Product::query(), '_')->count())->toBe(0);
});

test('matches through relation columns', function () {
    $shoes = Category::factory()->create(['name' => 'Shoes']);
    $bags = Category::factory()->create(['name' => 'Bags']);
    Product::factory()->for($shoes)->create(['title' => 'Runner']);
    Product::factory()->for($bags)->create(['title' => 'Tote']);

    $criterion = new TextSearchCriterion(['title', 'category.name']);

    expect($criterion->apply(Product::query(), 'shoes')->pluck('title')->all())->toBe(['Runner']);
});

test('a token may match the relation while another matches the model', function () {
    $shoes = Category::factory()->create(['name' => 'Shoes']);
    Product::factory()->for($shoes)->create(['title' => 'Blue Runner']);
    Product::factory()->for($shoes)->create(['title' => 'Red Runner']);

    $criterion = new TextSearchCriterion(['title', 'category.name']);

    expect($criterion->apply(Product::query(), 'shoes blue')->pluck('title')->all())->toBe(['Blue Runner']);
});

test('strips configured stripIdPrefixes from id column search value', function () {
    $product = Product::factory()->create(['title' => 'Plain']);
    Product::factory()->create(['title' => 'Other']);

    $criterion = new TextSearchCriterion(['id', 'title'], stripIdPrefixes: ['#']);

    expect($criterion->apply(Product::query(), "#{$product->id}")->pluck('id')->all())->toContain($product->id);
});

test('ors alternatives within a token group and ands groups', function () {
    Product::factory()->create(['title' => 'Red Sneakers']);
    Product::factory()->create(['title' => 'Blue Sneakers']);
    Product::factory()->create(['title' => 'Red Boots']);

    $criterion = new TextSearchCriterion(['title']);

    expect($criterion->apply(Product::query(), [['red'], ['sneakers', 'trainers']])->pluck('title')->all())
        ->toBe(['Red Sneakers']);
});

test('handles empty column array gracefully', function () {
    Product::factory()->count(2)->create();

    $criterion = new TextSearchCriterion([]);

    expect($criterion->apply(Product::query(), 'anything')->count())->toBe(2);
});
