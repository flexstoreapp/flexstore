import { router } from '@inertiajs/react';

import * as ShippingCarrierController from '@/actions/App/Http/Controllers/Admin/ShippingCarrierController';
import { SectionHeading } from '@/components/admin/section-heading';
import { AddCarrier } from '@/components/admin/shipping/add-carrier';
import { ManualCarrier } from '@/components/admin/shipping/manual-carrier';
import { Shippo } from '@/components/admin/shipping/shippo';
import { Shiprocket } from '@/components/admin/shipping/shiprocket';
import { __ } from '@/lib/i18n';
import { type ShippingCarrier } from '@/types';

interface CarriersProps {
    shippingCarriers: ShippingCarrier[];
}

export function Carriers({ shippingCarriers }: CarriersProps) {
    const manualCarriers = shippingCarriers.filter((carrier) => carrier.driver === 'manual');

    const handleToggle = (shippingCarrier: ShippingCarrier | undefined, checked: boolean) => {
        if (shippingCarrier) {
            router.patch(
                ShippingCarrierController.update(shippingCarrier),
                { is_active: checked },
                {
                    preserveScroll: true,
                    only: ['shippingCarriers'],
                },
            );
        }
    };

    return (
        <div className="space-y-6">
            <section className="space-y-4">
                <SectionHeading>{__('Integrated carriers')}</SectionHeading>

                <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                    <Shippo />
                    <Shiprocket />
                </div>
            </section>

            <section className="space-y-4">
                <SectionHeading>{__('Manual carriers')}</SectionHeading>

                <div className="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-4">
                    {manualCarriers.map((carrier) => (
                        <ManualCarrier key={carrier.id} shippingCarrier={carrier} onToggle={handleToggle} />
                    ))}

                    <AddCarrier />
                </div>
            </section>
        </div>
    );
}
