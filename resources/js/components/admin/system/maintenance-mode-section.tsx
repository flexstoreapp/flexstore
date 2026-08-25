import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as SystemSettingController from '@/actions/App/Http/Controllers/Admin/SystemSettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';

interface MaintenanceModeSectionProps {
    isDown: boolean;
    allowedIps: string[];
}

export function MaintenanceModeSection({ isDown, allowedIps }: MaintenanceModeSectionProps) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.maintenance_mode = data.maintenance_mode === 'on';
        return data;
    };

    return (
        <Form
            {...SystemSettingController.update.form()}
            options={{ preserveScroll: true, only: ['settings'] }}
            transform={handleTransform}
            setDefaultsOnSuccess
            className="space-y-12"
        >
            {({ processing, recentlySuccessful, errors }) => (
                <>
                    <UnsavedChangesAlert />

                    <div className="grid grid-cols-12 gap-6">
                        <div className="col-span-12 space-y-1 md:col-span-6">
                            <Label htmlFor="maintenance-mode" className="text-sm font-medium">
                                {__('Maintenance mode')}
                            </Label>
                            <HelpBlock>
                                {__('When enabled, customers see a maintenance page instead of your storefront')}
                            </HelpBlock>
                        </div>
                        <div className="col-span-12 space-y-2 md:col-span-6">
                            <Checkbox id="maintenance-mode" name="maintenance_mode" defaultChecked={isDown} />
                            <FieldError>{errors.maintenance_mode}</FieldError>
                        </div>
                    </div>

                    <Separator />

                    <div className="grid grid-cols-12 gap-6">
                        <div className="col-span-12 space-y-1 md:col-span-6">
                            <Label htmlFor="maintenance-allowed-ips" className="text-sm font-medium">
                                {__('Allowed IP addresses')}
                            </Label>
                            <HelpBlock>
                                {__('These addresses can browse the storefront while maintenance mode is active')}
                            </HelpBlock>
                        </div>
                        <div className="col-span-12 space-y-2 md:col-span-6">
                            <Textarea
                                id="maintenance-allowed-ips"
                                name="maintenance_allowed_ips"
                                defaultValue={allowedIps?.join('\n') ?? ''}
                                placeholder={'192.168.1.1\n10.0.0.1'}
                                rows={4}
                                className="max-h-40 font-mono text-sm"
                            />
                            <FieldError>{errors.maintenance_allowed_ips}</FieldError>
                        </div>
                    </div>

                    <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                        {__('Save changes')}
                    </FormSubmit>
                </>
            )}
        </Form>
    );
}
