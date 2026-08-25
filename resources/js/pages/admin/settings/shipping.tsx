import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as ShippingSettingController from '@/actions/App/Http/Controllers/Admin/ShippingSettingController';
import { Heading } from '@/components/admin/heading';
import { ProFeatureBanner } from '@/components/admin/pro/pro-feature-banner';
import { SectionHeading } from '@/components/admin/section-heading';
import { Carriers } from '@/components/admin/shipping/carriers';
import { ShippingRateList } from '@/components/admin/shipping/shipping-rate-list';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { __ } from '@/lib/i18n';
import { type Paginated, type ShippingCarrier, type ShippingRate } from '@/types';

interface ShippingProps {
    shippingRates: Paginated<ShippingRate>;
    shippingCarriers: ShippingCarrier[];
    filters: {
        query: string | null;
        page: number;
        sort: string;
        direction: string;
    };
}

export default function Shipping({ shippingRates, shippingCarriers, filters }: ShippingProps) {
    const { loading, searchLoading, searchQuery, getSortIcon, handleSort, handleSearchChange, handleFilterChange } =
        useListManagement({
            data: shippingRates.data,
            filters,
            fetchOnly: ['shippingRates'],
        });

    return (
        <>
            <Heading
                title={__('Shipping')}
                description={__('Shipping carriers and rates')}
                backHref={SettingController.index()}
            />

            <Carriers shippingCarriers={shippingCarriers} />

            <ProFeatureBanner
                title={__('Packing packages')}
                description={__('Save box sizes and customs details to buy carrier labels automatically.')}
            />
            <section className="space-y-4">
                <SectionHeading>{__('Shipping rates')}</SectionHeading>

                <ShippingRateList
                    shippingCarriers={shippingCarriers}
                    shippingRates={shippingRates}
                    searchQuery={searchQuery}
                    onSearchQueryChange={handleSearchChange}
                    getSortIcon={getSortIcon}
                    onSort={handleSort}
                    onPageChange={(page: number) => handleFilterChange('page', page)}
                    loading={loading}
                    searchLoading={searchLoading}
                />
            </section>
        </>
    );
}

Shipping.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Shipping'), href: ShippingSettingController.show() },
    ],
};
