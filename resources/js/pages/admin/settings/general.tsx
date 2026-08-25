import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as GeneralSettingController from '@/actions/App/Http/Controllers/Admin/GeneralSettingController';
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
import { __ } from '@/lib/i18n';
import type { GeneralSettings } from '@/types';

export default function General({ settings }: { settings: GeneralSettings }) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.auto_approve_reviews = data.auto_approve_reviews === 'on';
        return data;
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('General')}
                description={__('General settings and preferences')}
                backHref={SettingController.index()}
            />

            <Form
                {...GeneralSettingController.update.form()}
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
                                <Label htmlFor="default-low-stock-threshold" className="text-sm font-medium">
                                    {__('Default low stock threshold')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'Default threshold for low stock alerts. Products will show low stock warnings when their quantity falls at or below this number.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="default-low-stock-threshold"
                                    name="default_low_stock_threshold"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    defaultValue={String(settings.default_low_stock_threshold ?? '5')}
                                />
                                <FieldError>{errors.default_low_stock_threshold}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="auto-approve-reviews" className="text-sm font-medium">
                                    {__('Auto-approve reviews')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'When enabled, new reviews will be automatically approved. When disabled, reviews will require manual approval.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Checkbox
                                    id="auto-approve-reviews"
                                    name="auto_approve_reviews"
                                    defaultChecked={settings.auto_approve_reviews ?? false}
                                />
                                <FieldError>{errors.auto_approve_reviews}</FieldError>
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

General.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('General'), href: GeneralSettingController.show() },
    ],
};
