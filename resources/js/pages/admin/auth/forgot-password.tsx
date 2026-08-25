import { Head, Form } from '@inertiajs/react';

import * as PasswordResetLinkController from '@/actions/App/Http/Controllers/Admin/PasswordResetLinkController';
import * as SessionController from '@/actions/App/Http/Controllers/Admin/SessionController';
import { SubmitButton } from '@/components/admin/submit-button';
import { TextLink } from '@/components/admin/text-link';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __, __nodes } from '@/lib/i18n';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title={__('Forgot password')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    {status}
                </div>
            )}

            <Form {...PasswordResetLinkController.store.form()} className="space-y-6">
                {({ processing, errors }) => (
                    <>
                        <Field>
                            <FieldLabel htmlFor="email">{__('Email address')}</FieldLabel>
                            <Input id="email" type="email" name="email" autoFocus />
                            <FieldError>{errors.email}</FieldError>
                        </Field>

                        <SubmitButton className="w-full" processing={processing}>
                            {__('Email password reset link')}
                        </SubmitButton>

                        <div className="text-center text-sm text-muted-foreground">
                            {__nodes('Or, return to :link', {
                                link: <TextLink href={SessionController.create()}>{__('the login page')}</TextLink>,
                            })}
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ForgotPassword.layout = {
    title: __('Forgot password'),
    description: __('Enter your email to receive a password reset link'),
};
