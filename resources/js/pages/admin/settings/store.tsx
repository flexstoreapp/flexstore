import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type * as RPNInput from 'react-phone-number-input';

import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import * as StoreSettingController from '@/actions/App/Http/Controllers/Admin/StoreSettingController';
import { CountryPicker, type SelectableItem } from '@/components/admin/country-picker';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { InlineImageUploader } from '@/components/admin/inline-image-uploader';
import { ResourcePickerField } from '@/components/admin/resource-picker';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { FacebookIcon, InstagramIcon, PinterestIcon, TikTokIcon, XIcon, YoutubeIcon } from '@/components/brand-icons';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { PhoneInput } from '@/components/ui/phone-input';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useCountries } from '@/hooks/use-countries';
import { usePhoneCountries } from '@/hooks/use-phone-countries';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StoreSettings, TranslationKey } from '@/types';
import type { MediaItem } from '@/types/media';

const LOCALITY_COLUMNS: Record<number, string> = {
    1: 'sm:grid-cols-1',
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-3',
};

export default function Store({
    settings,
    assetMedia,
}: {
    settings: StoreSettings;
    assetMedia: Record<'store_logo' | 'store_logo_dark' | 'store_favicon', MediaItem | null>;
}) {
    const { countryOptions, countryNames } = useCountries({ all: true });
    const [countryCode, setCountryCode] = useState(settings.store_country_code ?? '');
    const allPhoneCountries = usePhoneCountries({ all: true });
    const [countryPickerOpen, setCountryPickerOpen] = useState(false);
    const [sellingCountries, setSellingCountries] = useState<SelectableItem[]>(
        (settings.selling_countries ?? []).map((code) => ({ code, name: countryNames[code] ?? code })),
    );
    const [state, setState] = useState(settings.store_state ?? '');
    const [phone, setPhone] = useState(settings.store_phone ?? '');
    const { fieldRules } = useAddressFieldRules(countryCode);
    const localityColumns =
        LOCALITY_COLUMNS[1 + (fieldRules.state.hidden ? 0 : 1) + (fieldRules.postal_code.hidden ? 0 : 1)];

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Store')}
                description={__('Store settings and preferences')}
                backHref={SettingController.index()}
            />

            <Form
                {...StoreSettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                transform={(data) => ({
                    ...data,
                    selling_countries: sellingCountries.map((country) => country.code),
                })}
                setDefaultsOnSuccess
                className="mb-6 space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="store-name" className="text-sm font-medium">
                                    {__('Store name')}
                                </FieldLabel>
                                <HelpBlock>{__('The name of your store as it appears to customers')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="store-name"
                                    name="store_name"
                                    type="text"
                                    defaultValue={settings.store_name ?? ''}
                                />
                                <FieldError>{errors.store_name}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="store-description" className="text-sm font-medium">
                                    {__('Store description')}
                                </FieldLabel>
                                <HelpBlock>
                                    {__('Brief description of your store that appears on the website')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Textarea
                                    id="store-description"
                                    name="store_description"
                                    defaultValue={settings.store_description ?? ''}
                                    rows={3}
                                    className="max-h-40"
                                />
                                <FieldError>{errors.store_description}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="store-email" className="text-sm font-medium">
                                    {__('Store email')}
                                </FieldLabel>
                                <HelpBlock>{__('Primary email for customer inquiries')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="store-email"
                                    name="store_email"
                                    type="email"
                                    defaultValue={settings.store_email ?? ''}
                                />
                                <FieldError>{errors.store_email}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="store-phone" className="text-sm font-medium">
                                    {__('Store phone')}
                                </FieldLabel>
                                <HelpBlock>{__('Primary phone number for customer inquiries')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <PhoneInput
                                    id="store-phone"
                                    name="store_phone"
                                    countries={allPhoneCountries}
                                    defaultCountry={countryCode as RPNInput.Country}
                                    value={phone}
                                    onChange={(value) => setPhone(value ?? '')}
                                />
                                <FieldError>{errors.store_phone}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Store address')}</Label>
                                <HelpBlock>{__('Physical address of your store')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <AdaptiveSelect
                                        id="store-country-code"
                                        name="store_country_code"
                                        value={countryCode}
                                        onValueChange={(value) => {
                                            setCountryCode(value);
                                            setState('');
                                        }}
                                        placeholder={__('Select a country')}
                                        options={countryOptions}
                                        search
                                    />
                                    <FieldError>{errors.store_country_code}</FieldError>
                                </Field>
                                <Field>
                                    <Input
                                        id="store-street-address"
                                        name="store_street_address"
                                        type="text"
                                        placeholder={__('Street address')}
                                        defaultValue={settings.store_street_address ?? ''}
                                    />
                                    <FieldError>{errors.store_street_address}</FieldError>
                                </Field>

                                <div className={cn('grid grid-cols-1 gap-4', localityColumns)}>
                                    <Field>
                                        <Input
                                            id="store-city"
                                            name="store_city"
                                            type="text"
                                            placeholder={__(fieldRules.city.label as TranslationKey)}
                                            defaultValue={settings.store_city ?? ''}
                                        />
                                        <FieldError>{errors.store_city}</FieldError>
                                    </Field>
                                    {!fieldRules.state.hidden && (
                                        <Field>
                                            {fieldRules.state.options ? (
                                                <AdaptiveSelect
                                                    id="store-state"
                                                    name="store_state"
                                                    value={state}
                                                    onValueChange={setState}
                                                    placeholder={__(fieldRules.state.label as TranslationKey)}
                                                    options={fieldRules.state.options}
                                                    search
                                                />
                                            ) : (
                                                <Input
                                                    id="store-state"
                                                    name="store_state"
                                                    type="text"
                                                    placeholder={__(fieldRules.state.label as TranslationKey)}
                                                    value={state}
                                                    onChange={(e) => setState(e.target.value)}
                                                />
                                            )}
                                            <FieldError>{errors.store_state}</FieldError>
                                        </Field>
                                    )}
                                    {!fieldRules.postal_code.hidden && (
                                        <Field>
                                            <Input
                                                id="store-postal_code"
                                                name="store_postal_code"
                                                type="text"
                                                placeholder={__(fieldRules.postal_code.label as TranslationKey)}
                                                defaultValue={settings.store_postal_code ?? ''}
                                            />
                                            <FieldError>{errors.store_postal_code}</FieldError>
                                        </Field>
                                    )}
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Countries you sell to')}</Label>
                                <HelpBlock>
                                    {__(
                                        'Only these countries are offered in address and phone number fields. Leave empty to sell to every country.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <ResourcePickerField>
                                    <ResourcePickerField.Browse onBrowse={() => setCountryPickerOpen(true)}>
                                        {__('Browse countries')}
                                    </ResourcePickerField.Browse>
                                    <ResourcePickerField.Tags>
                                        {sellingCountries.map((country) => (
                                            <ResourcePickerField.Tag
                                                key={country.code}
                                                name="selling_countries[]"
                                                value={country.code}
                                                onRemove={() =>
                                                    setSellingCountries((previous) =>
                                                        previous.filter((item) => item.code !== country.code),
                                                    )
                                                }
                                            >
                                                {country.name}
                                            </ResourcePickerField.Tag>
                                        ))}
                                    </ResourcePickerField.Tags>
                                </ResourcePickerField>
                                <CountryPicker
                                    open={countryPickerOpen}
                                    onOpenChange={setCountryPickerOpen}
                                    selectedItems={sellingCountries}
                                    onSelectionChange={setSellingCountries}
                                    includeAll
                                    multiple
                                />
                                <FieldError>{errors.selling_countries}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Logo')}</Label>
                                <HelpBlock>
                                    {__(
                                        'Upload logos for light and dark mode. If no logo is set, the store name will be shown.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 md:col-span-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2.5">
                                        <p className="text-xs font-medium text-muted-foreground">{__('Light mode')}</p>
                                        <InlineImageUploader
                                            id="store_logo"
                                            name="store_logo"
                                            defaultValue={assetMedia.store_logo}
                                            size="md"
                                            aspectRatio="auto"
                                        />
                                        <FieldError>{errors.store_logo}</FieldError>
                                    </div>
                                    <div className="space-y-2.5">
                                        <p className="text-xs font-medium text-muted-foreground">{__('Dark mode')}</p>
                                        <InlineImageUploader
                                            id="store_logo_dark"
                                            name="store_logo_dark"
                                            defaultValue={assetMedia.store_logo_dark}
                                            size="md"
                                            aspectRatio="auto"
                                        />
                                        <FieldError>{errors.store_logo_dark}</FieldError>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Favicon')}</Label>
                                <HelpBlock>
                                    {__('A small icon shown in browser tabs. Recommended size: 32x32 or 64x64 pixels.')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 md:col-span-6">
                                <InlineImageUploader
                                    id="store_favicon"
                                    name="store_favicon"
                                    defaultValue={assetMedia.store_favicon}
                                    size="sm"
                                    aspectRatio="square"
                                    preserveFormat
                                />
                                <FieldError>{errors.store_favicon}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Social media links')}</Label>
                                <HelpBlock>
                                    {__(
                                        'Add URLs for your social media profiles. Only platforms with a URL will be shown in the storefront.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <FacebookIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-facebook"
                                            name="store_social_facebook"
                                            type="url"
                                            placeholder="https://facebook.com/yourpage"
                                            defaultValue={settings.store_social_facebook ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_facebook}</FieldError>
                                </Field>
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <InstagramIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-instagram"
                                            name="store_social_instagram"
                                            type="url"
                                            placeholder="https://instagram.com/yourprofile"
                                            defaultValue={settings.store_social_instagram ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_instagram}</FieldError>
                                </Field>
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <XIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-x"
                                            name="store_social_x"
                                            type="url"
                                            placeholder="https://x.com/yourhandle"
                                            defaultValue={settings.store_social_x ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_x}</FieldError>
                                </Field>
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <TikTokIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-tiktok"
                                            name="store_social_tiktok"
                                            type="url"
                                            placeholder="https://tiktok.com/@yourprofile"
                                            defaultValue={settings.store_social_tiktok ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_tiktok}</FieldError>
                                </Field>
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <PinterestIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-pinterest"
                                            name="store_social_pinterest"
                                            type="url"
                                            placeholder="https://pinterest.com/yourprofile"
                                            defaultValue={settings.store_social_pinterest ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_pinterest}</FieldError>
                                </Field>
                                <Field>
                                    <InputGroup.Root>
                                        <InputGroup.Prefix>
                                            <YoutubeIcon className="size-4" />
                                        </InputGroup.Prefix>
                                        <InputGroup.Control
                                            id="store-social-youtube"
                                            name="store_social_youtube"
                                            type="url"
                                            placeholder="https://youtube.com/@yourchannel"
                                            defaultValue={settings.store_social_youtube ?? ''}
                                        />
                                    </InputGroup.Root>
                                    <FieldError>{errors.store_social_youtube}</FieldError>
                                </Field>
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

Store.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Store'), href: StoreSettingController.show() },
    ],
};
