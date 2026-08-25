import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import * as AnnouncementController from '@/actions/App/Http/Controllers/Admin/AnnouncementController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Checkbox } from '@/components/ui/checkbox';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useStorefrontBuilder } from '@/layouts/admin/storefront-builder-layout';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Announcement } from '@/types';

interface AnnouncementFormProps {
    announcement?: Announcement;
}

export function AnnouncementForm({ announcement }: AnnouncementFormProps) {
    const { reloadIframe } = useStorefrontBuilder();
    const formRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        formRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, []);

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_active = data.is_active === 'on';

        if (!data.starts_at) {
            data.starts_at = null;
        }
        if (!data.ends_at) {
            data.ends_at = null;
        }

        return data;
    };

    return (
        <div ref={formRef}>
            <Form
                {...(announcement
                    ? AnnouncementController.update.form(announcement)
                    : AnnouncementController.store.form())}
                options={{ preserveScroll: true, only: ['announcement'] }}
                onSuccess={() => reloadIframe()}
                transform={handleTransform}
                setDefaultsOnSuccess
            >
                {({ processing, errors, recentlySuccessful }) => (
                    <div className="mb-6 space-y-6 p-4 text-sm">
                        <UnsavedChangesAlert />
                        <Field>
                            <FieldLabel htmlFor="content">{__('Content')}</FieldLabel>
                            <Input
                                id="content"
                                name="content"
                                type="text"
                                placeholder={__('e.g., Free shipping on orders over $50!')}
                                defaultValue={getTranslation(announcement?.content)}
                                required
                            />
                            <FieldError>{errors.content}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="url">{__('URL')}</FieldLabel>
                            <Input
                                id="url"
                                name="url"
                                type="text"
                                placeholder="/shop"
                                defaultValue={announcement?.url ?? ''}
                            />
                            <FieldError>{errors.url}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="starts_at-date">{__('Start date & time')}</FieldLabel>
                            <DateTimePicker
                                id="starts_at"
                                name="starts_at"
                                defaultValue={announcement?.starts_at ?? undefined}
                            />
                            <FieldError>{errors.starts_at}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="ends_at-date">{__('End date & time')}</FieldLabel>
                            <DateTimePicker
                                id="ends_at"
                                name="ends_at"
                                defaultValue={announcement?.ends_at ?? undefined}
                            />
                            <FieldError>{errors.ends_at}</FieldError>
                        </Field>

                        <Field orientation="horizontal">
                            <Checkbox
                                id="is_active"
                                name="is_active"
                                defaultChecked={announcement?.is_active ?? true}
                            />
                            <FieldLabel htmlFor="is_active">{__('Show on storefront')}</FieldLabel>
                        </Field>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {announcement ? __('Update announcement') : __('Add announcement')}
                        </FormSubmit>
                    </div>
                )}
            </Form>
        </div>
    );
}
