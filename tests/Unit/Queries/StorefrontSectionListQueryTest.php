<?php

declare(strict_types=1);

use App\Models\StorefrontSection;
use App\Queries\StorefrontSectionListQuery;
use Illuminate\Database\Eloquent\Collection;

covers(StorefrontSectionListQuery::class);

uses()->group('queries', 'storefront');

test('returns all storefront sections', function () {
    StorefrontSection::factory()->count(3)->create();

    $query = new StorefrontSectionListQuery();
    $result = $query->execute();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(3);
});

test('returns only id, type, title and is_active columns', function () {
    StorefrontSection::factory()->create();

    $query = new StorefrontSectionListQuery();
    $result = $query->execute();

    $attributes = array_keys($result->first()->getAttributes());

    expect($attributes)->toBe(['id', 'type', 'title', 'is_active']);
});

test('orders sections by sort_order', function () {
    StorefrontSection::factory()->create(['sort_order' => 2, 'title' => 'Second']);
    StorefrontSection::factory()->create(['sort_order' => 1, 'title' => 'First']);

    $query = new StorefrontSectionListQuery();
    $result = $query->execute();

    expect($result->first()->title)->toBe('First')
        ->and($result->last()->title)->toBe('Second');
});

test('returns empty collection when no sections exist', function () {
    $query = new StorefrontSectionListQuery();
    $result = $query->execute();

    expect($result)->toBeEmpty();
});
