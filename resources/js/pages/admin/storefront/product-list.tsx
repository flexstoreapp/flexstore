import { router } from '@inertiajs/react';
import { useState } from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontProductListController from '@/actions/App/Http/Controllers/Admin/StorefrontProductListController';
import { RadioSetting } from '@/components/admin/storefront/radio-setting';
import { SelectSetting } from '@/components/admin/storefront/select-setting';
import { Separator } from '@/components/ui/separator';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import type { ListLoadingMethod, ProductListSettings, ProductSortOption } from '@/types';

const loadingMethodOptions = [
    {
        value: 'pagination',
        label: __('Pagination'),
        description: __('Traditional page numbers for navigation'),
    },
    {
        value: 'load_more',
        label: __('Load more button'),
        description: __('Button to load additional products'),
    },
    {
        value: 'infinite_scroll',
        label: __('Infinite scroll'),
        description: __('Automatically load products when scrolling'),
    },
];

const sortOptions = [
    { value: 'latest', label: __('Latest') },
    { value: 'price_asc', label: __('Price: Low to high') },
    { value: 'price_desc', label: __('Price: High to low') },
    { value: 'name_asc', label: __('Name: A to Z') },
    { value: 'name_desc', label: __('Name: Z to A') },
];

const productsPerPageOptions = [
    { value: '24', label: '24' },
    { value: '36', label: '36' },
    { value: '48', label: '48' },
    { value: '60', label: '60' },
];

export default function ProductList({ settings: initialSettings }: { settings: ProductListSettings }) {
    const [settings, setSettings] = useState(initialSettings);
    const { reloadIframe } = useStorefrontBuilder();

    const updateSetting = <K extends keyof ProductListSettings>(key: K, value: ProductListSettings[K]) => {
        setSettings((prev) => ({ ...prev, [key]: value }));

        router.patch(
            StorefrontProductListController.update(),
            { [`storefront_product_list_${key}`]: value },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <div className="mb-8 space-y-6 p-4">
            <RadioSetting
                label={__('Loading method')}
                description={__('Choose how more products are loaded')}
                options={loadingMethodOptions}
                value={settings.loading_method}
                onValueChange={(value) => updateSetting('loading_method', value as ListLoadingMethod)}
            />

            <Separator />

            <SelectSetting
                label={__('Default per page')}
                description={__('Number of products shown per page by default')}
                options={productsPerPageOptions}
                value={String(settings.default_per_page)}
                onValueChange={(value) => updateSetting('default_per_page', parseInt(value, 10))}
                className="w-30"
            />

            <SelectSetting
                label={__('Default sort')}
                description={__('Default sorting option for products')}
                options={sortOptions}
                value={settings.default_sort}
                onValueChange={(value) => updateSetting('default_sort', value as ProductSortOption)}
                className="w-48"
            />
        </div>
    );
}

ProductList.layout = {
    title: __('Product list'),
    backHref: StorefrontController.index(),
};
