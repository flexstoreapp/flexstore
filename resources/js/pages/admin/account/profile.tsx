import { Head, Form, usePage } from '@inertiajs/react';

import * as ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import { FormSubmit } from '@/components/admin/form-submit';
import { HeadingSmall } from '@/components/admin/heading-small';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { AdminSharedData } from '@/types';

export default function Profile() {
    const { auth } = usePage<AdminSharedData>().props;

    return (
        <>
            <Head title={__('Profile')} />

            <div className="space-y-6">
                <HeadingSmall title={__('Profile')} description={__('Update your name and email address')} />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    className="space-y-6"
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <Field>
                                <FieldLabel htmlFor="name">{__('Full name')}</FieldLabel>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={auth.user!.name}
                                    autoComplete="name"
                                    required
                                />
                                <FieldError>{errors.name}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="email">{__('Email address')}</FieldLabel>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={auth.user!.email}
                                    autoComplete="username"
                                    required
                                />
                                <FieldError>{errors.email}</FieldError>
                            </Field>

                            <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                                {__('Save profile')}
                            </FormSubmit>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [{ title: __('Profile'), href: ProfileController.edit() }],
};
