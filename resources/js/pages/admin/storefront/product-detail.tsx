import type { FormDataConvertible } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import * as StorefrontProductDetailController from '@/actions/App/Http/Controllers/Admin/StorefrontProductDetailController';
import { ProductDetailInfoStripFields } from '@/components/admin/storefront/product-detail-info-strip-fields';
import { SelectSetting } from '@/components/admin/storefront/select-setting';
import { SwitchSetting } from '@/components/admin/storefront/switch-setting';
import { Separator } from '@/components/ui/separator';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import type { ProductDetailSettings } from '@/types';

const relatedProductsCountOptions = [
    { value: '5', label: '5' },
    { value: '10', label: '10' },
    { value: '15', label: '15' },
    { value: '20', label: '20' },
];

const reviewsPerPageOptions = [
    { value: '5', label: '5' },
    { value: '10', label: '10' },
    { value: '15', label: '15' },
    { value: '20', label: '20' },
];

export default function ProductDetail({ settings: initialSettings }: { settings: ProductDetailSettings }) {
    const { errors } = usePage().props;
    const [settings, setSettings] = useState(initialSettings);
    const { reloadIframe } = useStorefrontBuilder();

    const patchSetting = (data: Record<string, FormDataConvertible>) => {
        router.patch(StorefrontProductDetailController.update(), data, {
            preserveScroll: true,
            only: ['settings'],
            onSuccess: () => reloadIframe(),
        });
    };

    return (
        <div className="mb-8 space-y-6 p-4">
            <div className="space-y-4">
                <SwitchSetting
                    label={__('Show info strip')}
                    description={__('Display the info strip on product pages')}
                    checked={settings.show_info_strip}
                    onCheckedChange={(checked) => {
                        setSettings((prev) => ({ ...prev, show_info_strip: checked }));
                        patchSetting({ storefront_product_detail_show_info_strip: checked });
                    }}
                />

                {settings.show_info_strip && (
                    <ProductDetailInfoStripFields items={settings.info_strip} errors={errors} />
                )}
            </div>

            <Separator />

            <div className="space-y-4">
                <SwitchSetting
                    label={__('Show reviews')}
                    description={__('Display customer reviews on product pages')}
                    checked={settings.show_reviews}
                    onCheckedChange={(checked) => {
                        setSettings((prev) => ({ ...prev, show_reviews: checked }));
                        patchSetting({ storefront_product_detail_show_reviews: checked });
                    }}
                />

                {settings.show_reviews && (
                    <SelectSetting
                        label={__('Reviews per page')}
                        description={__('Number of reviews to load at a time')}
                        options={reviewsPerPageOptions}
                        value={String(settings.reviews_per_page)}
                        onValueChange={(value) => {
                            const perPage = parseInt(value, 10);
                            setSettings((prev) => ({ ...prev, reviews_per_page: perPage }));
                            patchSetting({ storefront_product_detail_reviews_per_page: perPage });
                        }}
                        className="w-30"
                    />
                )}
            </div>

            <Separator />

            <div className="space-y-4">
                <SwitchSetting
                    label={__('Show related products')}
                    description={__('Display related products below the product details')}
                    checked={settings.show_related_products}
                    onCheckedChange={(checked) => {
                        setSettings((prev) => ({ ...prev, show_related_products: checked }));
                        patchSetting({ storefront_product_detail_show_related_products: checked });
                    }}
                />

                {settings.show_related_products && (
                    <SelectSetting
                        label={__('Number of products')}
                        description={__('How many related products to show')}
                        options={relatedProductsCountOptions}
                        value={String(settings.related_products_count)}
                        onValueChange={(value) => {
                            const count = parseInt(value, 10);
                            setSettings((prev) => ({ ...prev, related_products_count: count }));
                            patchSetting({ storefront_product_detail_related_products_count: count });
                        }}
                        className="w-30"
                    />
                )}
            </div>
        </div>
    );
}

ProductDetail.layout = {
    title: __('Product detail'),
    backHref: StorefrontController.index(),
};
