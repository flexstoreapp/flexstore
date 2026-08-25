import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as SeoSettingController from '@/actions/App/Http/Controllers/Admin/SeoSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import type { SeoSettings } from '@/types';

export default function Seo({ settings }: { settings: SeoSettings }) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.seo_robots_indexing = data.seo_robots_indexing === 'on';
        return data;
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('SEO')}
                description={__('Improve your site visibility')}
                backHref={SettingController.index()}
            />

            <Form
                {...SeoSettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                transform={handleTransform}
                setDefaultsOnSuccess
                className="mb-6 space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="seo-homepage-meta-title" className="text-sm font-medium">
                                    {__('Homepage meta title')}
                                </Label>
                                <HelpBlock>
                                    {__('Custom title for the homepage. Defaults to the store name if left empty.')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="seo-homepage-meta-title"
                                    name="seo_homepage_meta_title"
                                    type="text"
                                    defaultValue={settings.seo_homepage_meta_title ?? ''}
                                />
                                <FieldError>{errors.seo_homepage_meta_title}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="seo-homepage-meta-description" className="text-sm font-medium">
                                    {__('Homepage meta description')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'Description shown in search engine results for the homepage. Keep it under 160 characters for best results.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Textarea
                                    id="seo-homepage-meta-description"
                                    name="seo_homepage_meta_description"
                                    defaultValue={settings.seo_homepage_meta_description ?? ''}
                                    rows={3}
                                    className="max-h-24"
                                />
                                <FieldError>{errors.seo_homepage_meta_description}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="seo-shop-meta-title" className="text-sm font-medium">
                                    {__('Shop meta title')}
                                </Label>
                                <HelpBlock>
                                    {__('Custom title for the shop page. Defaults to the store name if left empty.')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="seo-shop-meta-title"
                                    name="seo_shop_meta_title"
                                    type="text"
                                    defaultValue={settings.seo_shop_meta_title ?? ''}
                                />
                                <FieldError>{errors.seo_shop_meta_title}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="seo-shop-meta-description" className="text-sm font-medium">
                                    {__('Shop meta description')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'Description shown in search engine results for the shop page. Keep it under 160 characters for best results.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Textarea
                                    id="seo-shop-meta-description"
                                    name="seo_shop_meta_description"
                                    defaultValue={settings.seo_shop_meta_description ?? ''}
                                    rows={3}
                                    className="max-h-24"
                                />
                                <FieldError>{errors.seo_shop_meta_description}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="seo-robots-indexing" className="text-sm font-medium">
                                    {__('Search engine indexing')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'Allow search engines to index your store. Disable this for staging or development environments.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Checkbox
                                    id="seo-robots-indexing"
                                    name="seo_robots_indexing"
                                    defaultChecked={settings.seo_robots_indexing ?? true}
                                />
                                <FieldError>{errors.seo_robots_indexing}</FieldError>
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

Seo.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('SEO'), href: SeoSettingController.show() },
    ],
};
