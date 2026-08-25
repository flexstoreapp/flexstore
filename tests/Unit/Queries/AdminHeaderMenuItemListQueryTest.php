<?php

declare(strict_types=1);

use App\Models\MenuItem;
use App\Queries\AdminHeaderMenuItemListQuery;
use Illuminate\Database\Eloquent\Collection;

covers(AdminHeaderMenuItemListQuery::class);

uses()->group('queries', 'menu-item');

test('returns header menu items', function () {
    MenuItem::factory()->header()->create(['parent_id' => null]);
    MenuItem::factory()->footer()->create(['parent_id' => null]);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(1);
});

test('returns only root items', function () {
    $parent = MenuItem::factory()->header()->create(['parent_id' => null]);
    MenuItem::factory()->header()->create(['parent_id' => $parent->id]);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($parent->id);
});

test('includes both active and inactive items', function () {
    MenuItem::factory()->header()->active()->create(['parent_id' => null]);
    MenuItem::factory()->header()->inactive()->create(['parent_id' => null]);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result)->toHaveCount(2);
});

test('eager loads children and related models', function () {
    $parent = MenuItem::factory()->header()->create(['parent_id' => null]);
    MenuItem::factory()->header()->create(['parent_id' => $parent->id]);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result->first()->relationLoaded('children'))->toBeTrue()
        ->and($result->first()->relationLoaded('brand'))->toBeTrue()
        ->and($result->first()->relationLoaded('category'))->toBeTrue();
});

test('orders items by sort_order', function () {
    MenuItem::factory()->header()->create(['parent_id' => null, 'sort_order' => 2, 'label' => 'Second']);
    MenuItem::factory()->header()->create(['parent_id' => null, 'sort_order' => 1, 'label' => 'First']);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result->first()->label)->toBe('First')
        ->and($result->last()->label)->toBe('Second');
});

test('returns empty collection when no header items exist', function () {
    MenuItem::factory()->footer()->create(['parent_id' => null]);

    $query = new AdminHeaderMenuItemListQuery();
    $result = $query->execute();

    expect($result)->toBeEmpty();
});
