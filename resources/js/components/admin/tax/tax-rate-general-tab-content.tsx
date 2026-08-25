import { useState } from 'react';

import { InfoPopover } from '@/components/admin/info-popover';
import { RegionPicker, type SelectableItem } from '@/components/admin/region-picker';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { CheckboxCard } from '@/components/ui/checkbox-card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { TaxCategoryOption, TaxRate } from '@/types';

interface TaxRateGeneralTabContentProps {
    taxRate?: TaxRate;
    taxCategories: TaxCategoryOption[];
    errors: Record<string, string>;
    selectedRegion: SelectableItem | null;
    onRegionSelect: (region: SelectableItem | null) => void;
}

export function TaxRateGeneralTabContent({
    taxRate,
    taxCategories,
    errors,
    selectedRegion,
    onRegionSelect,
}: TaxRateGeneralTabContentProps) {
    const [regionPickerOpen, setRegionPickerOpen] = useState(false);
    const [taxCategory, setTaxCategory] = useState<string>(() => taxRate?.tax_category ?? '');
    const [prevTaxRateId, setPrevTaxRateId] = useState<number | undefined>(taxRate?.id);

    if (taxRate?.id !== prevTaxRateId) {
        setPrevTaxRateId(taxRate?.id);
        setTaxCategory(taxRate?.tax_category ?? '');
    }

    return (
        <div className="mt-4 space-y-6">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor="tax-name">{__('Tax name')}</FieldLabel>
                    <Input
                        id="tax-name"
                        name="name"
                        type="text"
                        defaultValue={getTranslation(taxRate?.name)}
                        placeholder={__('VAT')}
                    />
                    <FieldError>{errors.name}</FieldError>
                </Field>

                <Field>
                    <FieldLabel htmlFor="rate">{__('Tax rate')}</FieldLabel>
                    <InputGroup.Root>
                        <InputGroup.Control
                            id="rate"
                            type="number"
                            inputMode="decimal"
                            min="0"
                            max="100"
                            step="0.01"
                            name="rate"
                            defaultValue={taxRate?.rate}
                            placeholder="0.00"
                        />
                        <InputGroup.Suffix>%</InputGroup.Suffix>
                    </InputGroup.Root>
                    <FieldError>{errors.rate}</FieldError>
                </Field>
            </div>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="priority">{__('Priority')}</FieldLabel>
                    <InfoPopover
                        className="w-100"
                        text={__(
                            'Taxes with higher priority are calculated first. When using compound taxes, each tax includes the previous tax in its calculation.',
                        )}
                    />
                </div>

                <Input
                    id="priority"
                    type="number"
                    name="priority"
                    inputMode="numeric"
                    min="0"
                    max="999"
                    step="1"
                    defaultValue={taxRate?.priority}
                    placeholder="0"
                />
                <FieldError>{errors.priority}</FieldError>
            </Field>

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
                    onSelectionChange={(region) => onRegionSelect(region)}
                />
                <FieldError>{errors.region_id}</FieldError>
            </Field>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="tax-category">{__('Tax category')}</FieldLabel>
                    <InfoPopover
                        className="w-90"
                        text={__(
                            'If no tax category is selected, this tax rate will be applicable for all tax categories.',
                        )}
                    />
                </div>

                <div className="flex">
                    <AdaptiveSelect
                        id="tax-category"
                        name="tax_category"
                        options={taxCategories}
                        value={taxCategory}
                        onValueChange={(value) => setTaxCategory(value)}
                        placeholder={__('Select a tax category')}
                        className={cn('flex-1', taxCategory && 'rounded-e-none')}
                    />

                    {taxCategory && (
                        <Button
                            type="button"
                            variant="outline"
                            size="md"
                            className="rounded-s-none border-s-0"
                            onClick={() => setTaxCategory('')}
                        >
                            {__('Remove')}
                        </Button>
                    )}
                </div>
                <FieldError>{errors.tax_category}</FieldError>
            </Field>

            <Field>
                <CheckboxCard
                    id="is-compound"
                    name="is_compound"
                    label={__('Compound tax')}
                    description={__('Compound taxes are calculated on top of other taxes')}
                    defaultChecked={taxRate?.is_compound}
                />
            </Field>

            <Field orientation="horizontal">
                <Checkbox id="is-active" name="is_active" defaultChecked={taxRate?.is_active ?? true} />
                <FieldLabel htmlFor="is-active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
