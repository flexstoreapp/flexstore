import { Head, Form } from '@inertiajs/react';
import { useRef } from 'react';

import * as PasswordController from '@/actions/App/Http/Controllers/Admin/PasswordController';
import { FormSubmit } from '@/components/admin/form-submit';
import { HeadingSmall } from '@/components/admin/heading-small';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title={__('Password')} />

            <div className="space-y-6">
                <HeadingSmall
                    title={__('Update password')}
                    description={__('Ensure your account is using a long, random password to stay secure')}
                />

                <Form
                    {...PasswordController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                    resetOnSuccess
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <Field>
                                <FieldLabel htmlFor="current_password">{__('Current password')}</FieldLabel>
                                <Input
                                    id="current_password"
                                    name="current_password"
                                    ref={currentPasswordInput}
                                    type="password"
                                    required
                                />
                                <FieldError>{errors.current_password}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="password">{__('New password')}</FieldLabel>
                                <Input id="password" name="password" ref={passwordInput} type="password" required />
                                <FieldError>{errors.password}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="password_confirmation">{__('Confirm password')}</FieldLabel>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                />
                                <FieldError>{errors.password_confirmation}</FieldError>
                            </Field>

                            <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                                {__('Save password')}
                            </FormSubmit>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Password.layout = {
    breadcrumbs: [{ title: __('Password'), href: PasswordController.edit() }],
};
