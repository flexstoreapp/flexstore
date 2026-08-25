import { Form } from '@inertiajs/react';
import { lazy, Suspense, useState } from 'react';

import * as PolicySettingController from '@/actions/App/Http/Controllers/Admin/PolicySettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { InfoPopover } from '@/components/admin/info-popover';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { CheckboxCard } from '@/components/ui/checkbox-card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { RichTextEditorSkeleton } from '@/components/ui/rich-text-editor-skeleton';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import type { PolicySettings } from '@/types';

const RichTextEditor = lazy(() =>
    import('@/components/ui/rich-text-editor').then((m) => ({ default: m.RichTextEditor })),
);

export default function Policy({ settings }: { settings: PolicySettings }) {
    const { open: openProUpgrade } = useProUpgrade();
    const [refundPolicy, setRefundPolicy] = useState(settings.refund_policy ?? '');
    const [privacyPolicy, setPrivacyPolicy] = useState(settings.privacy_policy ?? '');
    const [termsOfService, setTermsOfService] = useState(settings.terms_of_service ?? '');

    return (
        <>
            <Heading
                title={__('Policy')}
                description={__('Returns and legal policies')}
                backHref={SettingController.index()}
            />

            <Form
                {...PolicySettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                setDefaultsOnSuccess
                className="space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />
                        <div
                            className="grid cursor-pointer grid-cols-12 gap-4"
                            onClick={() => openProUpgrade(__('Returns'))}
                            role="presentation"
                        >
                            <div className="col-span-12 md:col-span-5">
                                <Label className="flex items-center gap-2 text-sm font-medium">
                                    {__('Return configuration')}
                                    <ProBadge />
                                </Label>
                                <HelpBlock className="mt-1">
                                    {__('Configure how customer returns are handled')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 w-full max-w-md space-y-6 opacity-60 md:col-span-7">
                                <Field>
                                    <CheckboxCard
                                        id="returns-enabled"
                                        label={__('Customer returns')}
                                        description={__('Let customers request returns for their delivered orders')}
                                        checked={false}
                                        disabled
                                    />
                                </Field>

                                <Field>
                                    <div className="flex items-center gap-2">
                                        <FieldLabel htmlFor="return-window-days">
                                            {__('Return window (days)')}
                                        </FieldLabel>
                                        <InfoPopover
                                            className="w-90"
                                            text={__(
                                                'How many days after delivery a customer can request a return. Set to 0 to allow returns at any time.',
                                            )}
                                        />
                                    </div>
                                    <Input
                                        id="return-window-days"
                                        className="w-full"
                                        type="number"
                                        inputMode="numeric"
                                        defaultValue="30"
                                        disabled
                                    />
                                </Field>

                                <Field>
                                    <div className="flex items-center gap-2">
                                        <FieldLabel htmlFor="restocking-fee-percent">{__('Restocking fee')}</FieldLabel>
                                        <InfoPopover
                                            className="w-90"
                                            text={__(
                                                'Default percentage deducted from a refund when items are returned. Set to 0 for no fee.',
                                            )}
                                        />
                                    </div>
                                    <InputGroup.Root className="w-full">
                                        <InputGroup.Control
                                            id="restocking-fee-percent"
                                            type="number"
                                            inputMode="decimal"
                                            defaultValue="0"
                                            disabled
                                        />
                                        <InputGroup.Suffix>%</InputGroup.Suffix>
                                    </InputGroup.Root>
                                </Field>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-4">
                            <div className="col-span-12 md:col-span-5">
                                <Label htmlFor="refund-policy" className="text-sm font-medium">
                                    {__('Refund policy')}
                                </Label>
                                <HelpBlock className="mt-1">
                                    {__('Explain your store refund and return policies')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-7">
                                <Suspense fallback={<RichTextEditorSkeleton />}>
                                    <RichTextEditor content={refundPolicy} onChange={setRefundPolicy} />
                                </Suspense>
                                <ReactiveHiddenInput name="refund_policy" value={refundPolicy} />
                                <FieldError>{errors.refund_policy}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-4">
                            <div className="col-span-12 md:col-span-5">
                                <Label htmlFor="privacy-policy" className="text-sm font-medium">
                                    {__('Privacy policy')}
                                </Label>
                                <HelpBlock className="mt-1">
                                    {__('Explain how your store collects and manages customer data')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-7">
                                <Suspense fallback={<RichTextEditorSkeleton />}>
                                    <RichTextEditor content={privacyPolicy} onChange={setPrivacyPolicy} />
                                </Suspense>
                                <ReactiveHiddenInput name="privacy_policy" value={privacyPolicy} />
                                <FieldError>{errors.privacy_policy}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-4">
                            <div className="col-span-12 md:col-span-5">
                                <Label htmlFor="terms-of-service" className="text-sm font-medium">
                                    {__('Terms of service')}
                                </Label>
                                <HelpBlock className="mt-1">
                                    {__('Outline the rules and guidelines for using your store')}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-7">
                                <Suspense fallback={<RichTextEditorSkeleton />}>
                                    <RichTextEditor content={termsOfService} onChange={setTermsOfService} />
                                </Suspense>
                                <ReactiveHiddenInput name="terms_of_service" value={termsOfService} />
                                <FieldError>{errors.terms_of_service}</FieldError>
                            </div>
                        </div>

                        <FormSubmit className="mb-6" processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>
        </>
    );
}

Policy.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Policy'), href: PolicySettingController.show() },
    ],
};
