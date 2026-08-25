<?php

declare(strict_types=1);

use App\DTOs\ProductFilterInput;

covers(ProductFilterInput::class);

uses()->group('dtos');

test('parses request-shaped data into typed values', function () {
    $input = ProductFilterInput::fromArray([
        'query' => 'shoes',
        'categories' => '1,2',
        'brands' => '3',
        'min_price' => '10',
        'max_price' => '90',
        'in_stock' => '1',
        'on_sale' => '1',
        'rating' => '4',
        'sort' => 'price_asc',
        'per_page' => '36',
        'page' => '2',
    ]);

    expect($input->search)->toBe('shoes')
        ->and($input->categoryIds)->toBe([1, 2])
        ->and($input->brandIds)->toBe([3])
        ->and($input->minPrice)->toBe('10')
        ->and($input->maxPrice)->toBe('90')
        ->and($input->inStock)->toBeTrue()
        ->and($input->onSale)->toBeTrue()
        ->and($input->rating)->toBe(4.0)
        ->and($input->sort)->toBe('price_asc')
        ->and($input->perPage)->toBe(36)
        ->and($input->page)->toBe(2);
});

test('defaults to null and empty values when data is missing', function () {
    $input = ProductFilterInput::fromArray([]);

    expect($input->search)->toBeNull()
        ->and($input->categoryIds)->toBe([])
        ->and($input->brandIds)->toBe([])
        ->and($input->inStock)->toBeNull()
        ->and($input->rating)->toBeNull()
        ->and($input->perPage)->toBeNull()
        ->and($input->page)->toBeNull()
        ->and($input->contextCategoryId)->toBeNull();
});

test('maps to a criteria filter set, omitting absent filters', function () {
    $filters = (new ProductFilterInput(
        search: 'shoes',
        categoryIds: [1],
        inStock: true,
        onSale: false,
        rating: 4.0,
    ))->toFilters('price_asc');

    expect($filters)->toBe([
        'sort' => 'price_asc',
        'query' => 'shoes',
        'categories' => [1],
        'in_stock' => true,
        'rating' => 4.0,
    ])
        ->and($filters)->not->toHaveKeys(['brands', 'min_price', 'max_price', 'on_sale']);
});

test('carries all fields when applying context', function () {
    $input = ProductFilterInput::fromArray(['query' => 'shoes', 'rating' => '3'])
        ->withContextCategory(7);

    expect($input->contextCategoryId)->toBe(7)
        ->and($input->search)->toBe('shoes')
        ->and($input->rating)->toBe(3.0);

    $brandScoped = $input->withContextBrand(9);

    expect($brandScoped->contextBrandId)->toBe(9)
        ->and($brandScoped->contextCategoryId)->toBe(7)
        ->and($brandScoped->search)->toBe('shoes');
});
