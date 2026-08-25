import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as TaxSettingController from '@/actions/App/Http/Controllers/Admin/TaxSettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { SectionHeading } from '@/components/admin/section-heading';
import { TaxRateList } from '@/components/admin/tax/tax-rate-list';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { CheckboxCard } from '@/components/ui/checkbox-card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { InputGroup } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { __ } from '@/lib/i18n';
import type { Paginated, TaxCategoryOption, TaxRate, TaxSettings } from '@/types';

interface TaxProps {
    settings: TaxSettings;
    taxRates: Paginated<TaxRate>;
    taxCategories: TaxCategoryOption[];
    filters: {
        query: string | null;
        page: number;
        sort: string;
        direction: string;
    };
}

export default function Tax({ settings, taxRates, taxCategories, filters }: TaxProps) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.prices_include_tax = data.prices_include_tax === 'on';
        data.shipping_is_taxable = data.shipping_is_taxable === 'on';
        return data;
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Tax')}
                description={__('Tax settings and preferences')}
                backHref={SettingController.index()}
            />

            <Form
                {...TaxSettingController.update.form()}
                options={{
                    preserveScroll: true,
                    only: ['settings'],
                }}
                transform={handleTransform}
                setDefaultsOnSuccess
                className="mb-10 space-y-6"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <div className="grid w-full grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Tax configuration')}</Label>
                                <HelpBlock>
                                    {__('Configure how taxes are calculated, applied, and displayed')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <FieldLabel htmlFor="default-tax-rate">{__('Default tax rate')}</FieldLabel>
                                    <InputGroup.Root>
                                        <InputGroup.Control
                                            id="default-tax-rate"
                                            type="number"
                                            inputMode="decimal"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            name="default_tax_rate"
                                            defaultValue={settings.default_tax_rate ?? ''}
                                            placeholder="0.00"
                                        />
                                        <InputGroup.Suffix>%</InputGroup.Suffix>
                                    </InputGroup.Root>
                                    <FieldError>{errors.default_tax_rate}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="tax-based-on">{__('Tax based on')}</FieldLabel>
                                    <AdaptiveSelect
                                        id="tax-based-on"
                                        name="tax_based_on"
                                        defaultValue={settings.tax_based_on ?? 'shipping'}
                                        options={[
                                            { value: 'shipping', label: __('Shipping address') },
                                            { value: 'billing', label: __('Billing address') },
                                            { value: 'store', label: __('Store address') },
                                        ]}
                                    />
                                    <FieldError>{errors.tax_based_on}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="prices-include-tax"
                                        name="prices_include_tax"
                                        label={__('Prices include tax')}
                                        description={__('If product prices in your store already include tax')}
                                        defaultChecked={settings.prices_include_tax ?? false}
                                    />
                                    <FieldError>{errors.prices_include_tax}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="shipping-is-taxable"
                                        name="shipping_is_taxable"
                                        label={__('Tax on shipping')}
                                        description={__('Apply tax to shipping costs')}
                                        defaultChecked={settings.shipping_is_taxable ?? false}
                                    />
                                    <FieldError>{errors.shipping_is_taxable}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="display-tax-totals">{__('Display tax totals')}</FieldLabel>
                                    <AdaptiveSelect
                                        id="display-tax-totals"
                                        name="display_tax_totals"
                                        defaultValue={settings.display_tax_totals ?? 'itemized'}
                                        options={[
                                            { value: 'itemized', label: __('Itemized') },
                                            { value: 'single', label: __('Single total') },
                                        ]}
                                    />
                                    <FieldError>{errors.display_tax_totals}</FieldError>
                                </Field>
                            </div>
                        </div>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>

            <section className="space-y-4">
                <SectionHeading>{__('Tax rates')}</SectionHeading>

                <TaxRateList taxRates={taxRates} taxCategories={taxCategories} filters={filters} />
            </section>
        </div>
    );
}

Tax.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Tax'), href: TaxSettingController.show() },
    ],
};
