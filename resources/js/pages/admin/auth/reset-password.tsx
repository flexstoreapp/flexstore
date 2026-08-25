import { Head, Form } from '@inertiajs/react';

import * as NewPasswordController from '@/actions/App/Http/Controllers/Admin/NewPasswordController';
import { SubmitButton } from '@/components/admin/submit-button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    return (
        <>
            <Head title={__('Reset password')} />

            <Form
                {...NewPasswordController.store.form()}
                className="space-y-6"
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="token" defaultValue={token} />

                        <Field>
                            <FieldLabel htmlFor="email">{__('Email address')}</FieldLabel>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                defaultValue={email}
                                className="bg-muted"
                                readOnly
                            />
                            <FieldError>{errors.email}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="password">{__('Password')}</FieldLabel>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                autoComplete="new-password"
                                autoFocus
                            />
                            <FieldError>{errors.password}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="password_confirmation">{__('Confirm password')}</FieldLabel>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autoComplete="new-password"
                            />
                            <FieldError>{errors.password_confirmation}</FieldError>
                        </Field>

                        <SubmitButton className="w-full" processing={processing}>
                            {__('Reset password')}
                        </SubmitButton>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: __('Reset password'),
    description: __('Please enter your new password below'),
};
