import { Link, router } from '@inertiajs/react';

import * as CheckoutController from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { TextField } from '@/components/storefront/text-field';
import { useInitials } from '@/hooks/use-initials';
import { __ } from '@/lib/i18n';

import { useCheckout } from './checkout-context';

export function ContactSection() {
    const { isAuthenticated, userEmail, name, email, setEmail, errors } = useCheckout();
    const getInitials = useInitials();
    const redirectTo = CheckoutController.create().url;

    return (
        <section aria-labelledby="co-contact-h" className="rounded-md border border-line bg-surface p-6 lg:p-7">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 id="co-contact-h" className="m-0 text-xl font-semibold text-ink">
                    {__('Contact')}
                </h2>
                {isAuthenticated ? (
                    <span className="text-sm text-muted">
                        {__('Not you?')}{' '}
                        <Link
                            href={SessionController.destroy({ query: { redirect_to: redirectTo } })}
                            as="button"
                            onClick={() => router.flushAll()}
                            className="font-semibold text-primary underline-offset-2 hover:underline"
                        >
                            {__('Log out')}
                        </Link>
                    </span>
                ) : (
                    <span className="text-sm text-muted">
                        {__('Have an account?')}{' '}
                        <a
                            href={SessionController.create({ query: { redirect_to: redirectTo } }).url}
                            className="font-semibold text-primary underline-offset-2 hover:underline"
                        >
                            {__('Log in')}
                        </a>
                    </span>
                )}
            </div>

            {isAuthenticated ? (
                <div className="mt-5 flex items-center gap-3.5 rounded-md border border-line-strong bg-surface-2 p-4">
                    <span
                        aria-hidden="true"
                        className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-md font-bold text-white"
                    >
                        {getInitials(name ?? '?')}
                    </span>
                    <div className="min-w-0">
                        {name && <div className="truncate font-semibold text-ink">{name}</div>}
                        <div className="truncate text-sm text-muted">{userEmail}</div>
                    </div>
                </div>
            ) : (
                <div className="mt-5">
                    <TextField
                        id="co-email"
                        label={__('Email address')}
                        type="email"
                        inputMode="email"
                        autoComplete="email"
                        placeholder="you@example.com"
                        value={email}
                        onChange={setEmail}
                        error={errors.customer_email}
                    />
                </div>
            )}
        </section>
    );
}
