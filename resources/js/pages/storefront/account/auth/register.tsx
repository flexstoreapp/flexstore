import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';

import * as PrivacyPolicyController from '@/actions/App/Http/Controllers/Storefront/PrivacyPolicyController';
import * as RegisterController from '@/actions/App/Http/Controllers/Storefront/RegisterController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import * as TermsOfServiceController from '@/actions/App/Http/Controllers/Storefront/TermsOfServiceController';
import { AuthCard } from '@/components/storefront/auth-card';
import { Button } from '@/components/storefront/button';
import { CheckboxField } from '@/components/storefront/checkbox-field';
import { PasswordField } from '@/components/storefront/password-field';
import { TextField } from '@/components/storefront/text-field';
import { __, __nodes } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export default function Register() {
    const [agreed, setAgreed] = useState(false);

    return (
        <AuthCard
            title={__('Create your account')}
            heading={__('Create your account')}
            subtitle={__('It only takes a minute to get started.')}
            footer={
                <>
                    {__('Already have an account?')}{' '}
                    <Link
                        href={SessionController.create()}
                        className="font-semibold text-primary underline-offset-2 hover:underline"
                    >
                        {__('Log in')}
                    </Link>
                </>
            }
        >
            <Form
                {...RegisterController.store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                className={cn('flex flex-col gap-5', 'mt-8')}
            >
                {({ processing, errors }) => (
                    <>
                        <TextField
                            id="name"
                            name="name"
                            required
                            label={__('Full name')}
                            autoComplete="name"
                            autoFocus
                            placeholder={__('Jane Doe')}
                            error={errors.name}
                        />

                        <TextField
                            id="email"
                            name="email"
                            required
                            label={__('Email address')}
                            type="email"
                            autoComplete="email"
                            placeholder="you@example.com"
                            error={errors.email}
                        />

                        <PasswordField
                            id="password"
                            name="password"
                            required
                            label={__('Password')}
                            autoComplete="new-password"
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

                        <CheckboxField
                            id="agree"
                            checked={agreed}
                            onChange={setAgreed}
                            className="w-full items-start"
                            label={
                                <span className="leading-snug">
                                    {__nodes('I agree to the :terms and :privacy_policy.', {
                                        terms: (
                                            <Link
                                                href={TermsOfServiceController.show()}
                                                className="font-semibold text-primary underline-offset-2 hover:underline"
                                            >
                                                {__('Terms of service')}
                                            </Link>
                                        ),
                                        privacy_policy: (
                                            <Link
                                                href={PrivacyPolicyController.show()}
                                                className="font-semibold text-primary underline-offset-2 hover:underline"
                                            >
                                                {__('Privacy policy')}
                                            </Link>
                                        ),
                                    })}
                                </span>
                            }
                        />

                        <Button type="submit" size="lg" disabled={!agreed} processing={processing} block>
                            {__('Create account')}
                        </Button>
                    </>
                )}
            </Form>
        </AuthCard>
    );
}
