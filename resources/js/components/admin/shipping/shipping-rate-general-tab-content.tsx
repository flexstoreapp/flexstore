import { useState } from 'react';

import { type SelectableItem, RegionPicker } from '@/components/admin/region-picker';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Region, ShippingCarrier, ShippingRate, ShippingRateType } from '@/types';

type RegionSelection = Region | SelectableItem | null;

interface ShippingRateGeneralTabContentProps {
    region?: Region;
    shippingCarriers: ShippingCarrier[];
    shippingRate?: ShippingRate;
    errors: Record<string, string>;
}

export function ShippingRateGeneralTabContent({
    region,
    shippingCarriers,
    shippingRate,
    errors,
}: ShippingRateGeneralTabContentProps) {
    const [rateType, setRateType] = useState<ShippingRateType>(shippingRate?.type ?? 'flat');
    const [regionPickerOpen, setRegionPickerOpen] = useState(false);
    const [selectedRegion, setSelectedRegion] = useState<RegionSelection>(region ?? shippingRate?.region ?? null);
    const [selectedCarrierId, setSelectedCarrierId] = useState<string>(String(shippingRate?.shipping_carrier_id ?? ''));
    const { currencyFormat } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps();

    const shippingCarrierOptions = shippingCarriers.map((shippingCarrier) => {
        return {
            value: String(shippingCarrier.id),
            label: getTranslation(shippingCarrier.name),
        };
    });

    const rateTypeOptions = [
        { value: 'flat', label: __('Flat rate') },
        { value: 'free', label: __('Free shipping') },
    ];

    return (
        <div className="mt-4 space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor="region">{__('Region')}</FieldLabel>
                    <ResourcePickerTrigger
                        id="region"
                        name="region_id"
                        value={selectedRegion?.id}
                        label={getTranslation(selectedRegion?.name)}
                        placeholder={__('Select a region')}
                        onOpen={() => setRegionPickerOpen(true)}
                    />
                    <RegionPicker
                        open={regionPickerOpen}
                        onOpenChange={setRegionPickerOpen}
                        selectedItems={selectedRegion ? [selectedRegion] : []}
                        onSelectionChange={(selected) => setSelectedRegion(selected)}
                    />
                    <FieldError>{errors.region_id}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor="shipping-carrier">{__('Shipping carrier')}</FieldLabel>
                    <AdaptiveSelect
                        id="shipping-carrier"
                        name="shipping_carrier_id"
                        value={selectedCarrierId}
                        onValueChange={setSelectedCarrierId}
                        placeholder={__('Select a carrier')}
                        options={shippingCarrierOptions}
                    />
                    <FieldError>{errors.shipping_carrier_id}</FieldError>
                </Field>
            </div>
            <Field>
                <FieldLabel htmlFor="rate-name">{__('Shipping name')}</FieldLabel>
                <Input
                    id="rate-name"
                    type="text"
                    name="name"
                    defaultValue={getTranslation(shippingRate?.name)}
                    placeholder={__('e.g., Express shipping')}
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="rate">{__('Shipping rate')}</FieldLabel>
                <InputGroup.Root>
                    <InputGroup.Prefix>{currencyFormat.symbol}</InputGroup.Prefix>
                    <InputGroup.Control
                        {...moneyInputProps}
                        id="rate"
                        name="rate"
                        defaultValue={shippingRate?.rate ?? ''}
                        disabled={rateType === 'free'}
                        placeholder={rateType === 'free' ? __('Free') : moneyInputProps.placeholder}
                    />
                    <InputGroup.Select
                        name="type"
                        value={rateType}
                        onValueChange={(value) => setRateType(value as ShippingRateType)}
                        options={rateTypeOptions}
                    />
                </InputGroup.Root>
                <FieldError>{errors.rate}</FieldError>
                <FieldError>{errors.type}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="delivery-time">{__('Delivery time')}</FieldLabel>
                <Input
                    id="delivery-time"
                    name="delivery_time"
                    type="text"
                    defaultValue={getTranslation(shippingRate?.delivery_time)}
                    placeholder={__('1-2 business days')}
                />
                <FieldError>{errors.delivery_time}</FieldError>
            </Field>
            <Field orientation="horizontal">
                <Checkbox id="is-active" name="is_active" defaultChecked={shippingRate?.is_active ?? true} />
                <FieldLabel htmlFor="is-active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
