import { Form } from '@inertiajs/react';

import SendTestMailController from '@/actions/App/Http/Controllers/Admin/SendTestMailController';
import { SubmitButton } from '@/components/admin/submit-button';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogContentContainer,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';

interface SendTestMailDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    defaultRecipient: string;
    onSent: () => void;
}

export function SendTestMailDialog({ open, onOpenChange, defaultRecipient, onSent }: SendTestMailDialogProps) {
    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...SendTestMailController.form()}
                    options={{ preserveScroll: true, preserveState: true }}
                    onSuccess={onSent}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Send test email')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {__('Send a test message using your saved settings')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Field>
                                    <FieldLabel htmlFor="recipient">{__('Recipient')}</FieldLabel>
                                    <Input
                                        id="recipient"
                                        name="recipient"
                                        type="email"
                                        placeholder="you@example.com"
                                        defaultValue={defaultRecipient}
                                        autoFocus
                                    />
                                    <FieldError>{errors.recipient}</FieldError>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose asChild>
                                    <Button type="button" variant="ghost">
                                        {__('Cancel')}
                                    </Button>
                                </AdaptiveDialogClose>
                                <SubmitButton processing={processing}>{__('Send test email')}</SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
