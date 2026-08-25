import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import * as SecurityController from '@/actions/App/Http/Controllers/Admin/SecurityController';
import * as TwoFactorAuthenticationController from '@/actions/App/Http/Controllers/Admin/TwoFactorAuthenticationController';
import { ManagePasskeys } from '@/components/admin/auth/manage-passkeys';
import { type Passkey } from '@/components/admin/auth/passkey-item';
import { TwoFactorRecoveryCodes } from '@/components/admin/auth/two-factor-recovery-codes';
import { TwoFactorDialog } from '@/components/admin/auth/two-factor-setup-dialog';
import { HeadingSmall } from '@/components/admin/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { HelpBlock } from '@/components/ui/help-block';
import { Separator } from '@/components/ui/separator';
import { useTwoFactorAuth } from '@/hooks/admin/use-two-factor-auth';
import { __ } from '@/lib/i18n';

interface SecurityProps {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
    recoveryCodes: string[];
    passkeys: Passkey[];
}

export default function Security({ requiresConfirmation, twoFactorEnabled, recoveryCodes, passkeys }: SecurityProps) {
    const [setupDialogOpen, setSetupDialogOpen] = useState(false);
    const { qrCodeSvg, hasSetupData, manualSetupKey, clearSetupData, fetchSetupData, errors } = useTwoFactorAuth();

    return (
        <>
            <Head title={__('Security')} />

            <div className="space-y-12">
                <div className="space-y-6">
                    <HeadingSmall
                        title={__('Two-factor auth')}
                        description={__('Manage your two-factor authentication settings')}
                    />
                    {twoFactorEnabled ? (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="default">{__('Enabled')}</Badge>
                            <HelpBlock>
                                {__(
                                    'With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported app.',
                                )}
                            </HelpBlock>

                            <TwoFactorRecoveryCodes recoveryCodes={recoveryCodes} />

                            <div className="relative inline">
                                <Form {...TwoFactorAuthenticationController.destroy.form()}>
                                    {({ processing }) => (
                                        <Button variant="destructive" type="submit" disabled={processing}>
                                            {__('Disable 2FA')}
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>
                    ) : (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="destructive">{__('Disabled')}</Badge>
                            <HelpBlock>
                                {__(
                                    'When you enable two-factor authentication, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported app.',
                                )}
                            </HelpBlock>

                            <div>
                                {hasSetupData ? (
                                    <Button onClick={() => setSetupDialogOpen(true)}>{__('Continue setup')}</Button>
                                ) : (
                                    <Form
                                        {...TwoFactorAuthenticationController.store.form()}
                                        onSuccess={() => setSetupDialogOpen(true)}
                                    >
                                        {({ processing }) => (
                                            <Button type="submit" disabled={processing}>
                                                {__('Enable 2FA')}
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    )}

                    <TwoFactorDialog
                        isOpen={setupDialogOpen}
                        onClose={() => setSetupDialogOpen(false)}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        clearSetupData={clearSetupData}
                        fetchSetupData={fetchSetupData}
                        errors={errors}
                    />
                </div>

                <Separator />

                <div className="space-y-6">
                    <HeadingSmall
                        title={__('Passkeys')}
                        description={__('Log in securely without a password using your device or security key')}
                    />

                    <ManagePasskeys passkeys={passkeys} />
                </div>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [{ title: __('Security'), href: SecurityController.show() }],
};
