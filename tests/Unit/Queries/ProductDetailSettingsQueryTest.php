<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Setting;
use App\Queries\ProductDetailSettingsQuery;

covers(ProductDetailSettingsQuery::class);

uses()->group('queries');

beforeEach(function () {
    Setting::query()->where('key', 'like', 'storefront_product_detail_%')->delete();
});

test('execute returns default settings when no settings exist', function () {
    $query = app(ProductDetailSettingsQuery::class);
    $result = $query->execute();

    expect($result)
        ->toBeArray()
        ->toHaveKey('show_info_strip', true)
        ->toHaveKey('info_strip', [])
        ->toHaveKey('show_related_products', true)
        ->toHaveKey('related_products_count', 10)
        ->toHaveKey('show_reviews', true)
        ->toHaveKey('reviews_per_page', 10);
});

test('execute returns stored settings when they exist', function () {
    Setting::factory()->create([
        'key' => 'storefront_product_detail_show_info_strip',
        'value' => false,
        'type' => SettingType::Boolean,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_detail_show_related_products',
        'value' => false,
        'type' => SettingType::Boolean,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_detail_related_products_count',
        'value' => 8,
        'type' => SettingType::Integer,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_detail_show_reviews',
        'value' => false,
        'type' => SettingType::Boolean,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_detail_reviews_per_page',
        'value' => 20,
        'type' => SettingType::Integer,
        'group' => SettingGroup::Storefront,
    ]);

    $query = app(ProductDetailSettingsQuery::class);
    $result = $query->execute();

    expect($result['show_info_strip'])->toBeFalse()
        ->and($result['show_related_products'])->toBeFalse()
        ->and($result['related_products_count'])->toBe(8)
        ->and($result['show_reviews'])->toBeFalse()
        ->and($result['reviews_per_page'])->toBe(20);
});

test('execute casts numeric values correctly', function () {
    Setting::factory()->create([
        'key' => 'storefront_product_detail_related_products_count',
        'value' => '6',
        'type' => SettingType::Text,
        'group' => SettingGroup::Storefront,
    ]);

    Setting::factory()->create([
        'key' => 'storefront_product_detail_reviews_per_page',
        'value' => '15',
        'type' => SettingType::Text,
        'group' => SettingGroup::Storefront,
    ]);

    $query = app(ProductDetailSettingsQuery::class);
    $result = $query->execute();

    expect($result['related_products_count'])->toBeInt()->toBe(6)
        ->and($result['reviews_per_page'])->toBeInt()->toBe(15);
});
