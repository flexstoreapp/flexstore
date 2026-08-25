<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Setting;
use App\Queries\ProductListSettingsQuery;

covers(ProductListSettingsQuery::class);

uses()->group('queries');

beforeEach(function () {
    Setting::query()->where('key', 'like', 'storefront_product_list_%')->delete();
});

test('execute returns default settings when no settings exist', function () {
    $query = app(ProductListSettingsQuery::class);
    $result = $query->execute();

    expect($result)
        ->toBeArray()
        ->toHaveKey('loading_method', ListLoadingMethod::Pagination->value)
        ->toHaveKey('default_per_page', 24)
        ->toHaveKey('default_sort', ProductSortOption::Latest->value);
});

test('execute returns stored settings when they exist', function () {
    Setting::factory()->create([
        'key' => 'storefront_product_list_loading_method',
        'value' => ListLoadingMethod::InfiniteScroll->value,
        'type' => SettingType::Text,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_list_default_per_page',
        'value' => 36,
        'type' => SettingType::Integer,
        'group' => SettingGroup::Storefront,
    ]);

    $query = app(ProductListSettingsQuery::class);
    $result = $query->execute();

    expect($result['loading_method'])->toBe(ListLoadingMethod::InfiniteScroll->value)
        ->and($result['default_per_page'])->toBe(36);
});

test('execute casts numeric values correctly', function () {
    Setting::factory()->create([
        'key' => 'storefront_product_list_default_per_page',
        'value' => '48',
        'type' => SettingType::Text,
        'group' => SettingGroup::Storefront,
    ]);

    $query = app(ProductListSettingsQuery::class);

    expect($query->execute()['default_per_page'])->toBeInt()->toBe(48);
});

test('execute returns stored default sort', function () {
    Setting::factory()->create([
        'key' => 'storefront_product_list_default_sort',
        'value' => ProductSortOption::PriceLowHigh->value,
        'type' => SettingType::Text,
        'group' => SettingGroup::Storefront,
    ]);

    $query = app(ProductListSettingsQuery::class);

    expect($query->execute()['default_sort'])->toBe(ProductSortOption::PriceLowHigh->value);
});
