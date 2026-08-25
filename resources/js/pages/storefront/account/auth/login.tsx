import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';

import * as PasswordResetLinkController from '@/actions/App/Http/Controllers/Storefront/PasswordResetLinkController';
import * as RegisterController from '@/actions/App/Http/Controllers/Storefront/RegisterController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { AuthCard } from '@/components/storefront/auth-card';
import { AuthStatus } from '@/components/storefront/auth-status';
import { Button } from '@/components/storefront/button';
import { CheckboxField } from '@/components/storefront/checkbox-field';
import { PasswordField } from '@/components/storefront/password-field';
import { TextField } from '@/components/storefront/text-field';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export default function Login() {
    const [remember, setRemember] = useState(true);

    return (
        <AuthCard
            title={__('Log in')}
            heading={__('Welcome back')}
            subtitle={__('Log in to your account to continue.')}
            footer={
                <>
                    {__('Don’t have an account?')}{' '}
                    <Link
                        href={RegisterController.create()}
                        className="font-semibold text-primary underline-offset-2 hover:underline"
                    >
                        {__('Create an account')}
                    </Link>
                </>
            }
        >
            <AuthStatus />

            <Form
                {...SessionController.store.form()}
                transform={(data) => ({ ...data, remember })}
                resetOnError={['password']}
                className={cn('flex flex-col gap-5', 'mt-8')}
            >
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

                        <PasswordField
                            id="password"
                            name="password"
                            required
                            label={__('Password')}
                            autoComplete="current-password"
                            placeholder={__('Enter your password')}
                            error={errors.password}
                            labelAction={
                                <Link
                                    href={PasswordResetLinkController.create()}
                                    className="text-sm font-semibold text-primary underline-offset-2 hover:underline"
                                >
                                    {__('Forgot password?')}
                                </Link>
                            }
                        />

                        <CheckboxField
                            id="remember"
                            label={__('Keep me signed in')}
                            checked={remember}
                            onChange={setRemember}
                        />

                        <Button type="submit" size="lg" processing={processing} block>
                            {__('Log in')}
                        </Button>
                    </>
                )}
            </Form>
        </AuthCard>
    );
}
