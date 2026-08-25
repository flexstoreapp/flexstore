import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Form } from '@inertiajs/react';

import * as PasskeyLoginController from '@/actions/App/Http/Controllers/Admin/PasskeyLoginController';
import * as PasskeyLoginOptionController from '@/actions/App/Http/Controllers/Admin/PasskeyLoginOptionController';
import * as PasswordResetLinkController from '@/actions/App/Http/Controllers/Admin/PasswordResetLinkController';
import * as SessionController from '@/actions/App/Http/Controllers/Admin/SessionController';
import { PasskeyVerify } from '@/components/admin/auth/passkey-verify';
import { SubmitButton } from '@/components/admin/submit-button';
import { TextLink } from '@/components/admin/text-link';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

export default function Login({ status }: { status?: string }) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.remember = data.remember === 'on';
        return data;
    };

    return (
        <>
            <Head title={__('Log in')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    {status}
                </div>
            )}

            <Form
                {...SessionController.store.form()}
                transform={handleTransform}
                resetOnError={['password']}
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <Field>
                            <FieldLabel htmlFor="email">{__('Email address')}</FieldLabel>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                tabIndex={1}
                                required
                                autoFocus
                            />
                            <FieldError>{errors.email}</FieldError>
                        </Field>

                        <Field>
                            <div className="flex items-center justify-between">
                                <FieldLabel htmlFor="password">{__('Password')}</FieldLabel>
                                <TextLink href={PasswordResetLinkController.create()} tabIndex={6} className="text-sm">
                                    {__('Forgot password?')}
                                </TextLink>
                            </div>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                tabIndex={2}
                                required
                            />
                            <FieldError>{errors.password}</FieldError>
                        </Field>

                        <Field orientation="horizontal">
                            <Checkbox id="remember" name="remember" tabIndex={3} />
                            <FieldLabel htmlFor="remember">{__('Remember me')}</FieldLabel>
                        </Field>

                        <SubmitButton className="w-full" processing={processing} tabIndex={4}>
                            {__('Log in')}
                        </SubmitButton>
                    </>
                )}
            </Form>

            <PasskeyVerify
                options={PasskeyLoginOptionController.show().url}
                submit={PasskeyLoginController.store().url}
                label={__('Log in with a passkey')}
            />
        </>
    );
}

Login.layout = {
    title: __('Welcome back'),
    description: __('Enter your email and password below to log in'),
};
