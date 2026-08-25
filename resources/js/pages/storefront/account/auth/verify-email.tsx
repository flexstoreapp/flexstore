import { Form } from '@inertiajs/react';

import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Storefront/EmailVerificationNotificationController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { AuthCard } from '@/components/storefront/auth-card';
import { AuthStatus } from '@/components/storefront/auth-status';
import { Button } from '@/components/storefront/button';
import { __ } from '@/lib/i18n';

export default function VerifyEmail() {
    return (
        <AuthCard
            title={__('Verify your email')}
            heading={__('Verify your email')}
            subtitle={__('We sent a verification link to your email address. Click it to activate your account.')}
        >
            <AuthStatus />

            <Form {...EmailVerificationNotificationController.form()} className="mt-8">
                {({ processing }) => (
                    <Button type="submit" size="lg" processing={processing} block>
                        {__('Resend verification email')}
                    </Button>
                )}
            </Form>

            <Form {...SessionController.destroy.form()} className="mt-4">
                <button
                    type="submit"
                    className="w-full text-center text-sm text-muted transition-colors hover:text-primary"
                >
                    {__('Log out')}
                </button>
            </Form>
        </AuthCard>
    );
}
