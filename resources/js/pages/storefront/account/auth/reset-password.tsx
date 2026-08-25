import { Form } from '@inertiajs/react';

import * as NewPasswordController from '@/actions/App/Http/Controllers/Storefront/NewPasswordController';
import { AuthCard } from '@/components/storefront/auth-card';
import { Button } from '@/components/storefront/button';
import { PasswordField } from '@/components/storefront/password-field';
import { TextField } from '@/components/storefront/text-field';
import { __ } from '@/lib/i18n';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    return (
        <AuthCard
            title={__('Set a new password')}
            heading={__('Set a new password')}
            subtitle={__('Choose a new password for your account.')}
        >
            <Form
                {...NewPasswordController.store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                className="mt-8 flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="token" value={token} />

                        <TextField
                            id="email"
                            name="email"
                            required
                            label={__('Email address')}
                            type="email"
                            autoComplete="email"
                            defaultValue={email}
                            readOnly
                            error={errors.email}
                        />

                        <PasswordField
                            id="password"
                            name="password"
                            required
                            label={__('New password')}
                            autoComplete="new-password"
                            autoFocus
                            placeholder={__('At least 8 characters')}
                            error={errors.password}
                        />

                        <PasswordField
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            label={__('Confirm password')}
                            autoComplete="new-password"
                            placeholder={__('Re-enter your password')}
                            error={errors.password_confirmation}
                        />

                        <Button type="submit" size="lg" processing={processing} block>
                            {__('Reset password')}
                        </Button>
                    </>
                )}
            </Form>
        </AuthCard>
    );
}
