import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as LanguageSettingController from '@/actions/App/Http/Controllers/Admin/LanguageSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import type { AvailableLocale, LanguageSettings } from '@/types';

interface LanguageProps {
    settings: LanguageSettings;
    availableLanguages: AvailableLocale[];
}

export default function Language({ settings, availableLanguages }: LanguageProps) {
    const { open: openProUpgrade } = useProUpgrade();
    const [defaultLocale, setDefaultLocale] = useState(settings.default_locale ?? 'en');

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Language')}
                description={__('Language settings and preferences')}
                backHref={SettingController.index()}
            />

            <Form
                {...LanguageSettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                setDefaultsOnSuccess
                transform={(data) => ({
                    ...data,
                    available_locales: [defaultLocale],
                })}
                className="mb-6 space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="default-locale" className="text-sm font-medium">
                                    {__('Default language')}
                                </FieldLabel>
                                <HelpBlock>
                                    {__(
                                        'The default language for your store. This is used when no locale preference is set.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Field>
                                    <AdaptiveSelect
                                        id="default-locale"
                                        name="default_locale"
                                        value={defaultLocale}
                                        onValueChange={setDefaultLocale}
                                        placeholder={__('Select a language')}
                                        options={availableLanguages.map((lang) => ({
                                            value: lang.code,
                                            label: `${lang.name} (${lang.code})`,
                                        }))}
                                    />
                                    <FieldError>{errors.default_locale}</FieldError>
                                </Field>
                            </div>
                        </div>

                        <Separator />

                        <div
                            className="grid cursor-pointer grid-cols-12 gap-6"
                            onClick={() => openProUpgrade(__('Multi-language'))}
                            role="presentation"
                        >
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="flex items-center gap-2 text-sm font-medium">
                                    {__('Available languages')}
                                    <ProBadge />
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'Select which languages are available for visitors to choose from. The language switcher will only appear when multiple languages are enabled.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 grid grid-cols-2 gap-4 opacity-60 md:col-span-6">
                                {availableLanguages.map((lang) => (
                                    <div key={lang.code} className="flex items-center gap-3">
                                        <Checkbox
                                            id={`locale-${lang.code}`}
                                            checked={lang.code === defaultLocale}
                                            disabled
                                        />
                                        <FieldLabel htmlFor={`locale-${lang.code}`}>
                                            {lang.name} ({lang.code})
                                        </FieldLabel>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>
        </div>
    );
}

Language.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Language'), href: LanguageSettingController.show() },
    ],
};
