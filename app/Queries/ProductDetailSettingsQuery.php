<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\SettingGroup;
use App\Models\Setting;

final readonly class ProductDetailSettingsQuery
{
    /**
     * @return array{
     *     show_info_strip: bool,
     *     info_strip: list<array{icon_name: string, title: array<string, string>, subtitle: array<string, string>}>,
     *     show_related_products: bool,
     *     related_products_count: int,
     *     show_reviews: bool,
     *     reviews_per_page: int,
     * }
     */
    public function execute(): array
    {
        $settings = Setting::getByGroup(SettingGroup::Storefront);

        return [
            'show_info_strip' => (bool) $settings->get('storefront_product_detail_show_info_strip', true),
            'info_strip' => $settings->get('storefront_product_detail_info_strip', []),
            'show_related_products' => (bool) $settings->get('storefront_product_detail_show_related_products', true),
            'related_products_count' => (int) $settings->get('storefront_product_detail_related_products_count', 10),
            'show_reviews' => (bool) $settings->get('storefront_product_detail_show_reviews', true),
            'reviews_per_page' => (int) $settings->get('storefront_product_detail_reviews_per_page', 10),
        ];
    }
}
