import { Form } from '@inertiajs/react';
import { useMemo } from 'react';

import * as CurrencySettingController from '@/actions/App/Http/Controllers/Admin/CurrencySettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { ProFeatureBanner } from '@/components/admin/pro/pro-feature-banner';
import { SectionHeading } from '@/components/admin/section-heading';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Field, FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { useCurrencies } from '@/hooks/admin/use-currencies';
import { __ } from '@/lib/i18n';
import type { Currency, CurrencySettings } from '@/types';

interface CurrencyProps {
    settings: CurrencySettings;
    currencies: Currency[];
}

export default function Currency({ settings, currencies }: CurrencyProps) {
    const { currencyNames } = useCurrencies();
    const activeCurrencies = useMemo(
        () => currencies.filter((c) => c.is_active).sort((a, b) => a.code.localeCompare(b.code)),
        [currencies],
    );
    const currencyOptions = activeCurrencies.map((currency) => ({
        value: currency.code,
        label: `${currencyNames[currency.code]} (${currency.code})`,
    }));

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Currency')}
                description={__('Currency settings and preferences')}
                backHref={SettingController.index()}
            />

            <Form
                {...CurrencySettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                setDefaultsOnSuccess
                className="mb-8 space-y-8"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <div className="grid w-full grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Base currency')}</Label>
                                <HelpBlock>
                                    {__(
                                        'The base currency in which all prices are stored. Changing this does not convert existing prices.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <AdaptiveSelect
                                        id="base-currency"
                                        name="base_currency"
                                        options={currencyOptions}
                                        defaultValue={settings.base_currency}
                                    />
                                    <FieldError>{errors.base_currency}</FieldError>
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
                <SectionHeading>{__('Currencies')}</SectionHeading>

                <ProFeatureBanner
                    title={__('Multi-currency')}
                    description={__('Sell in more than one currency and let customers switch with FlexStore Pro.')}
                />
            </section>
        </div>
    );
}

Currency.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Currency'), href: CurrencySettingController.show() },
    ],
};
