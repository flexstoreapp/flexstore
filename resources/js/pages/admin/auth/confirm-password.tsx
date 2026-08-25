import { Head, Form } from '@inertiajs/react';

import * as ConfirmablePasswordController from '@/actions/App/Http/Controllers/Admin/ConfirmablePasswordController';
import * as PasskeyConfirmationController from '@/actions/App/Http/Controllers/Admin/PasskeyConfirmationController';
import * as PasskeyConfirmationOptionController from '@/actions/App/Http/Controllers/Admin/PasskeyConfirmationOptionController';
import { PasskeyVerify } from '@/components/admin/auth/passkey-verify';
import { SubmitButton } from '@/components/admin/submit-button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

export default function ConfirmPassword() {
    return (
        <>
            <Head title={__('Confirm password')} />

            <Form {...ConfirmablePasswordController.store.form()} className="space-y-6" resetOnSuccess>
                {({ processing, errors }) => (
                    <>
                        <Field>
                            <FieldLabel htmlFor="password">{__('Password')}</FieldLabel>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                placeholder={__('Password')}
                                autoComplete="password"
                                autoFocus
                            />
                            <FieldError>{errors.password}</FieldError>
                        </Field>

                        <SubmitButton className="w-full" processing={processing}>
                            {__('Confirm password')}
                        </SubmitButton>
                    </>
                )}
            </Form>

            <PasskeyVerify
                options={PasskeyConfirmationOptionController.show().url}
                submit={PasskeyConfirmationController.store().url}
                label={__('Confirm with a passkey')}
            />
        </>
    );
}

ConfirmPassword.layout = {
    title: __('Confirm your password'),
    description: __('This is a secure area of the application. Please confirm your password before continuing.'),
};
