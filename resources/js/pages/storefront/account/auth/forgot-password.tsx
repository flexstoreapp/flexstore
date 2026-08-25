import { Form, Link } from '@inertiajs/react';

import * as PasswordResetLinkController from '@/actions/App/Http/Controllers/Storefront/PasswordResetLinkController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { AuthCard } from '@/components/storefront/auth-card';
import { AuthStatus } from '@/components/storefront/auth-status';
import { Button } from '@/components/storefront/button';
import { TextField } from '@/components/storefront/text-field';
import { __ } from '@/lib/i18n';

export default function ForgotPassword() {
    return (
        <AuthCard
            title={__('Reset your password')}
            heading={__('Reset your password')}
            subtitle={__('Enter your email and we will send you a link to reset your password.')}
            footer={
                <>
                    {__('Remembered your password?')}{' '}
                    <Link
                        href={SessionController.create()}
                        className="font-semibold text-primary underline-offset-2 hover:underline"
                    >
                        {__('Log in')}
                    </Link>
                </>
            }
        >
            <AuthStatus />

            <Form {...PasswordResetLinkController.store.form()} className="mt-8 flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <TextField
                            id="email"
                            name="email"
                            required
                            label={__('Email address')}
                            type="email"
                            autoComplete="email"
                            autoFocus
                            placeholder="you@example.com"
                            error={errors.email}
                        />

                        <Button type="submit" size="lg" processing={processing} block>
                            {__('Email password reset link')}
                        </Button>
                    </>
                )}
            </Form>
        </AuthCard>
    );
}
