import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { CountryPicker, type SelectableItem } from '@/components/admin/country-picker';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { InfoPopover } from '@/components/admin/info-popover';
import { ResourcePickerField } from '@/components/admin/resource-picker';
import { SubmitButton } from '@/components/admin/submit-button';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogContentContainer,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { TagsInput } from '@/components/ui/tags-input';
import { useStateOptions } from '@/hooks/use-address-field-rules';
import { useCountries } from '@/hooks/use-countries';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import { type Region } from '@/types';

interface RegionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    region?: Region;
}

export function RegionDialog({ open, onOpenChange, region }: RegionDialogProps) {
    const [countryPickerOpen, setCountryPickerOpen] = useState(false);
    const [selectedCountries, setSelectedCountries] = useState<SelectableItem[]>(() => getSelectableRegion(region, {}));
    const [selectedStates, setSelectedStates] = useState<string[]>(() => region?.states ?? []);
    const [selectedPostalCodes, setSelectedPostalCodes] = useState<string[]>(() => region?.postal_codes ?? []);
    const [prevRegionId, setPrevRegionId] = useState<number | undefined>(region?.id);
    const { countryNames } = useCountries();
    const formatDate = useFormatDate();
    const { options: stateOptions } = useStateOptions(selectedCountries.map((c) => c.code));

    if (region?.id !== prevRegionId) {
        setPrevRegionId(region?.id);
        setSelectedCountries(getSelectableRegion(region, countryNames));
        setSelectedStates(region?.states ?? []);
        setSelectedPostalCodes(region?.postal_codes ?? []);
    }

    const addCountries = (countries: SelectableItem[]) => {
        const existingCodes = new Set(selectedCountries.map((c) => c.code));
        const newCountries = countries.filter((c) => !existingCodes.has(c.code));
        setSelectedCountries((prev) => [...prev, ...newCountries]);
    };

    const removeCountry = (country: SelectableItem) => {
        setSelectedCountries((prev) => prev.filter((c) => c.code !== country.code));
    };

    const addState = (value: string) => {
        setSelectedStates((prev) => (prev.includes(value) ? prev : [...prev, value]));
    };

    const removeState = (index: number) => {
        const newStates = [...selectedStates];
        newStates.splice(index, 1);
        setSelectedStates(newStates);
    };

    const stateLabel = (value: string) => stateOptions?.find((o) => o.value === value)?.label ?? value;

    const addPostalCode = (value: string) => {
        setSelectedPostalCodes((prev) => [...prev, value]);
    };

    const removePostalCode = (index: number) => {
        const newPostalCodes = [...selectedPostalCodes];
        newPostalCodes.splice(index, 1);
        setSelectedPostalCodes(newPostalCodes);
    };

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.countries = data.countries ?? [];
        data.states = data.states ?? [];
        data.postal_codes = data.postal_codes ?? [];
        data.is_active = data.is_active === 'on';

        return data;
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...(region ? RegionController.update.form(region.id) : RegionController.store.form())}
                    options={{ preserveScroll: true, only: ['regions'] }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <FormDirtySignal
                                signal={`${selectedCountries.map((c) => c.code).join(',')}|${selectedStates.join(
                                    ',',
                                )}|${selectedPostalCodes.join(',')}`}
                            />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {region ? __('Edit region') : __('Add region')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {region
                                        ? __('Last updated on :datetime', { datetime: formatDate(region.updated_at) })
                                        : __('Add a new region to your store')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Field>
                                    <FieldLabel htmlFor="name">{__('Region name')}</FieldLabel>
                                    <Input id="name" name="name" defaultValue={getTranslation(region?.name)} />
                                    <FieldError>{errors.name}</FieldError>
                                </Field>
                                <Field>
                                    <FieldLabel>{__('Countries')}</FieldLabel>

                                    <ResourcePickerField>
                                        <ResourcePickerField.Browse onBrowse={() => setCountryPickerOpen(true)}>
                                            {__('Browse countries')}
                                        </ResourcePickerField.Browse>
                                        <ResourcePickerField.Tags>
                                            {selectedCountries.map((country) => (
                                                <ResourcePickerField.Tag
                                                    key={country.code}
                                                    name="countries[]"
                                                    value={country.code}
                                                    onRemove={() => removeCountry(country)}
                                                >
                                                    {country.name}
                                                </ResourcePickerField.Tag>
                                            ))}
                                        </ResourcePickerField.Tags>
                                    </ResourcePickerField>

                                    <CountryPicker
                                        includeAll
                                        open={countryPickerOpen}
                                        onOpenChange={setCountryPickerOpen}
                                        selectedItems={selectedCountries}
                                        onSelectionChange={addCountries}
                                        multiple
                                    />
                                    <FieldError>{errors.countries}</FieldError>
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="states">{__('States')}</FieldLabel>
                                    <TagsInput
                                        id="states"
                                        tags={selectedStates}
                                        onAdd={addState}
                                        onRemove={removeState}
                                        suggestions={stateOptions ?? undefined}
                                        getTagLabel={stateLabel}
                                    />
                                    {selectedStates.map((state) => (
                                        <input key={state} type="hidden" name="states[]" value={state} />
                                    ))}
                                    <FieldError>{errors.states}</FieldError>
                                </Field>
                                <Field>
                                    <div className="flex items-center gap-2">
                                        <FieldLabel htmlFor="postal_codes">{__('Postal codes')}</FieldLabel>
                                        <InfoPopover
                                            className="w-100"
                                            text={__(
                                                'Add an exact postal code (90210 or 100-0001), a prefix wildcard (902*), or a numeric range (90001..90099). Wildcards work with letters too (SW1*).',
                                            )}
                                        />
                                    </div>
                                    <TagsInput
                                        id="postal_codes"
                                        tags={selectedPostalCodes}
                                        onAdd={addPostalCode}
                                        onRemove={removePostalCode}
                                        placeholder={__('e.g., 90210, 902*, 90001..90099')}
                                    />

                                    {selectedPostalCodes.map((postal_code) => (
                                        <input
                                            key={postal_code}
                                            type="hidden"
                                            name="postal_codes[]"
                                            value={postal_code}
                                        />
                                    ))}
                                    <FieldError>{errors.postal_codes}</FieldError>
                                </Field>
                                <Field orientation="horizontal">
                                    <Checkbox
                                        id="is-active"
                                        name="is_active"
                                        defaultChecked={region?.is_active ?? true}
                                    />
                                    <FieldLabel htmlFor="is-active">{__('Active')}</FieldLabel>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {region ? __('Update region') : __('Add region')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}

function getSelectableRegion(region: Region | undefined, countryNames: Record<string, string>): SelectableItem[] {
    if (!region?.countries) return [];
    return region.countries.map((code) => ({ code, name: countryNames[code] }));
}
