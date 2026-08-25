import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as PasswordController from '@/actions/App/Http/Controllers/Storefront/PasswordController';
import * as ProfileController from '@/actions/App/Http/Controllers/Storefront/ProfileController';
import { Button } from '@/components/storefront/button';
import { Dialog } from '@/components/storefront/dialog';
import { PasswordField } from '@/components/storefront/password-field';
import { SavedLabel } from '@/components/storefront/saved-label';
import { TextField } from '@/components/storefront/text-field';
import { UnsavedChangesAlert } from '@/components/storefront/unsaved-changes-alert';
import { __ } from '@/lib/i18n';
import type { StorefrontSharedData } from '@/types';

export default function Profile() {
    const { auth } = usePage<StorefrontSharedData>().props;
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    return (
        <div className="flex max-w-2xl flex-col gap-6">
            <section className="rounded-md border border-line bg-surface p-5 sm:p-6">
                <h2 className="m-0 mb-5 text-xl font-semibold text-ink">{__('Personal details')}</h2>
                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-col gap-5"
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <TextField
                                id="name"
                                name="name"
                                required
                                label={__('Full name')}
                                autoComplete="name"
                                defaultValue={auth.user?.name}
                                error={errors.name}
                            />
                            <TextField
                                id="email"
                                name="email"
                                required
                                label={__('Email address')}
                                type="email"
                                autoComplete="email"
                                defaultValue={auth.user?.email}
                                error={errors.email}
                            />
                            <div className="flex items-center justify-end gap-3">
                                <SavedLabel visible={recentlySuccessful} />
                                <Button type="submit" size="md" processing={processing}>
                                    {__('Save changes')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </section>

            <section className="rounded-md border border-line bg-surface p-5 sm:p-6">
                <h2 className="m-0 mb-5 text-xl font-semibold text-ink">{__('Password')}</h2>
                <Form
                    {...PasswordController.update.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['current_password', 'password', 'password_confirmation']}
                    className="flex flex-col gap-5"
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <PasswordField
                                id="current_password"
                                name="current_password"
                                required
                                label={__('Current password')}
                                autoComplete="current-password"
                                error={errors.current_password}
                            />
                            <PasswordField
                                id="password"
                                name="password"
                                required
                                label={__('New password')}
                                autoComplete="new-password"
                                placeholder={__('At least 8 characters')}
                                error={errors.password}
                            />
                            <PasswordField
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                label={__('Confirm new password')}
                                autoComplete="new-password"
                                error={errors.password_confirmation}
                            />
                            <div className="flex items-center justify-end gap-3">
                                <SavedLabel visible={recentlySuccessful} />
                                <Button type="submit" size="md" processing={processing}>
                                    {__('Update password')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </section>

            <section className="rounded-md border border-error/40 bg-surface p-5 sm:p-6">
                <h2 className="m-0 mb-2 text-xl font-semibold text-ink">{__('Delete account')}</h2>
                <p className="mt-0 mb-4 text-sm leading-relaxed text-muted">
                    {__(
                        'Permanently delete your account, saved addresses and access to your downloads. Past orders stay in the store’s records.',
                    )}
                </p>
                <Button variant="error" size="md" onClick={() => setConfirmingDelete(true)}>
                    {__('Delete account')}
                </Button>
            </section>

            <Dialog
                open={confirmingDelete}
                onClose={() => setConfirmingDelete(false)}
                title={__('Delete account')}
                autoFocus
            >
                <Form {...ProfileController.destroy.form()} className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <p className="mt-0 mb-0 leading-relaxed text-ink">
                                {__(
                                    'This permanently deletes your account and saved addresses. Past orders stay in the store’s records. Enter your password to confirm.',
                                )}
                            </p>
                            <PasswordField
                                id="delete_password"
                                name="password"
                                label={__('Password')}
                                autoComplete="current-password"
                                required
                                error={errors.password}
                            />
                            <div className="flex justify-end gap-3">
                                <Button variant="outline" size="md" onClick={() => setConfirmingDelete(false)}>
                                    {__('No, keep it')}
                                </Button>
                                <Button type="submit" variant="error" size="md" processing={processing}>
                                    {__('Yes, delete')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </Dialog>
        </div>
    );
}

Profile.title = __('Profile');
