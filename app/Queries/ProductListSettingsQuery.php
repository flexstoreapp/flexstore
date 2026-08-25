<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Enums\SettingGroup;
use App\Models\Setting;

final readonly class ProductListSettingsQuery
{
    /**
     * @return array{
     *     loading_method: string,
     *     default_per_page: int,
     *     default_sort: string,
     * }
     */
    public function execute(): array
    {
        $settings = Setting::getByGroup(SettingGroup::Storefront);

        return [
            'loading_method' => $settings->get('storefront_product_list_loading_method', ListLoadingMethod::Pagination->value),
            'default_per_page' => (int) $settings->get('storefront_product_list_default_per_page', 24),
            'default_sort' => $settings->get('storefront_product_list_default_sort', ProductSortOption::Latest->value),
        ];
    }
}
