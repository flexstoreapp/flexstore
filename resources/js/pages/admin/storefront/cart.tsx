import { router } from '@inertiajs/react';
import { useState } from 'react';

import * as StorefrontCartController from '@/actions/App/Http/Controllers/Admin/StorefrontCartController';
import * as StorefrontController from '@/actions/App/Http/Controllers/Admin/StorefrontController';
import { SwitchSetting } from '@/components/admin/storefront/switch-setting';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import type { CartSettings } from '@/types';

export default function Cart({ settings: initialSettings }: { settings: CartSettings }) {
    const [settings, setSettings] = useState(initialSettings);
    const { reloadIframe } = useStorefrontBuilder();

    const handleCrossSellsChange = (checked: boolean) => {
        setSettings((prev) => ({ ...prev, show_cross_sells: checked }));

        router.patch(
            StorefrontCartController.update(),
            { storefront_cart_show_cross_sells: checked },
            {
                preserveScroll: true,
                only: ['settings'],
                onSuccess: () => reloadIframe(),
            },
        );
    };

    return (
        <div className="mb-8 space-y-6 p-4">
            <div className="space-y-4">
                <SwitchSetting
                    label={__('Show cross-sells')}
                    description={__('Display hand-picked cross-sells in the cart and the cart drawer')}
                    checked={settings.show_cross_sells}
                    onCheckedChange={handleCrossSellsChange}
                />
            </div>
        </div>
    );
}

Cart.layout = {
    title: __('Cart'),
    backHref: StorefrontController.index(),
};
