import { Form, usePage } from '@inertiajs/react';
import { CheckCircle2Icon } from 'lucide-react';
import { useState } from 'react';

import * as MailSettingController from '@/actions/App/Http/Controllers/Admin/MailSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { SendTestMailDialog } from '@/components/admin/mail/send-test-mail-dialog';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import type { AdminSharedData, MailSettings } from '@/types';

const ENCRYPTION_OPTIONS = [
    { value: '', label: __('None') },
    { value: 'tls', label: __('TLS') },
    { value: 'ssl', label: __('SSL') },
];

export default function Mail({ settings }: { settings: MailSettings }) {
    const { auth } = usePage<AdminSharedData>().props;
    const [testDialogOpen, setTestDialogOpen] = useState(false);
    const [showTestSentIndicator, setShowTestSentIndicator] = useState(false);

    const isConfigured = !!settings.mail_host;

    const handleTestSent = () => {
        setTestDialogOpen(false);
        setShowTestSentIndicator(true);
        setTimeout(() => setShowTestSentIndicator(false), 3000);
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Mail')}
                description={__('SMTP server and sender details')}
                backHref={SettingController.index()}
            >
                {showTestSentIndicator && (
                    <span className="mx-2 inline-flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400">
                        <CheckCircle2Icon className="size-4" />
                        {__('Test email sent')}
                    </span>
                )}
                <Button
                    type="button"
                    variant="outline"
                    disabled={!isConfigured}
                    onClick={() => setTestDialogOpen(true)}
                >
                    {__('Send test email')}
                </Button>
            </Heading>

            <Form
                {...MailSettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                setDefaultsOnSuccess
                className="mb-6 space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="mail-from-address" className="text-sm font-medium">
                                    {__('From address')}
                                </FieldLabel>
                                <HelpBlock>{__('Sender email shown to customers')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="mail-from-address"
                                    name="mail_from_address"
                                    type="email"
                                    placeholder="hello@example.com"
                                    defaultValue={settings.mail_from_address ?? ''}
                                />
                                <FieldError>{errors.mail_from_address}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <FieldLabel htmlFor="mail-from-name" className="text-sm font-medium">
                                    {__('From name')}
                                </FieldLabel>
                                <HelpBlock>{__('Display name shown alongside the sender address')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="mail-from-name"
                                    name="mail_from_name"
                                    type="text"
                                    placeholder={__('Your store name')}
                                    defaultValue={settings.mail_from_name ?? ''}
                                />
                                <FieldError>{errors.mail_from_name}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('SMTP server')}</Label>
                                <HelpBlock>{__('Connection details from your email provider')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <Input
                                        id="mail-host"
                                        name="mail_host"
                                        type="text"
                                        placeholder={__('Host')}
                                        defaultValue={settings.mail_host ?? ''}
                                    />
                                    <FieldError>{errors.mail_host}</FieldError>
                                </Field>

                                <div className="grid grid-cols-2 gap-4">
                                    <Field>
                                        <Input
                                            id="mail-port"
                                            name="mail_port"
                                            type="number"
                                            inputMode="numeric"
                                            min="1"
                                            max="65535"
                                            placeholder={__('Port')}
                                            defaultValue={settings.mail_port == null ? '' : String(settings.mail_port)}
                                        />
                                        <FieldError>{errors.mail_port}</FieldError>
                                    </Field>
                                    <Field>
                                        <AdaptiveSelect
                                            id="mail-encryption"
                                            name="mail_encryption"
                                            options={ENCRYPTION_OPTIONS}
                                            defaultValue={settings.mail_encryption ?? ''}
                                            placeholder={__('Encryption')}
                                        />
                                        <FieldError>{errors.mail_encryption}</FieldError>
                                    </Field>
                                </div>

                                <Field>
                                    <Input
                                        id="mail-username"
                                        name="mail_username"
                                        type="text"
                                        autoComplete="off"
                                        placeholder={__('Username')}
                                        defaultValue={settings.mail_username ?? ''}
                                    />
                                    <FieldError>{errors.mail_username}</FieldError>
                                </Field>

                                <Field>
                                    <Input
                                        id="mail-password"
                                        name="mail_password"
                                        type="text"
                                        autoComplete="off"
                                        placeholder={__('Password')}
                                        defaultValue={settings.mail_password ?? ''}
                                    />
                                    <FieldError>{errors.mail_password}</FieldError>
                                </Field>
                            </div>
                        </div>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>

            <SendTestMailDialog
                open={testDialogOpen}
                onOpenChange={setTestDialogOpen}
                defaultRecipient={auth.user?.email ?? ''}
                onSent={handleTestSent}
            />
        </div>
    );
}

Mail.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Mail'), href: MailSettingController.show() },
    ],
};
