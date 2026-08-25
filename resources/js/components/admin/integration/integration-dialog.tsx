import { Form } from '@inertiajs/react';

import * as IntegrationSettingController from '@/actions/App/Http/Controllers/Admin/IntegrationSettingController';
import { SubmitButton } from '@/components/admin/submit-button';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';

export interface IntegrationDialogRenderProps {
    errors: Record<string, string>;
}

interface IntegrationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    isConnected: boolean;
    activateLabel?: string;
    children: (props: IntegrationDialogRenderProps) => React.ReactNode;
}

export function IntegrationDialog({
    open,
    onOpenChange,
    title,
    description,
    isConnected,
    activateLabel,
    children,
}: IntegrationDialogProps) {
    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...IntegrationSettingController.update.form()}
                    options={{ preserveScroll: true, only: ['settings'] }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{title}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>{description}</AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <div className="space-y-4 px-6 md:px-0">{children({ errors })}</div>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton processing={processing}>
                                    {isConnected ? __('Save changes') : (activateLabel ?? __('Connect'))}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
