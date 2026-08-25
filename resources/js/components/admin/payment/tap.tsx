import { Settings2Icon } from 'lucide-react';
import { useState } from 'react';

import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { HelpBlock } from '@/components/ui/help-block';
import { Switch } from '@/components/ui/switch';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { PaymentGatewayDialog, type AdditionalTab, type PaymentGatewayDialogTab } from './payment-gateway-dialog';
import { TapCredentialsForm } from './tap-credentials';
import { TapWebhookTab } from './tap-webhook-tab';

const tabFields: Record<PaymentGatewayDialogTab, string[]> = {
    general: ['name', 'credentials.public_key', 'credentials.secret_key', 'credentials.checkout_mode', 'is_active'],
    conditions: [
        'min_order_value',
        'max_order_value',
        'min_weight',
        'min_weight_unit',
        'max_weight',
        'max_weight_unit',
    ],
    restrictions: [
        'excluded_products',
        'excluded_products.0',
        'excluded_categories',
        'excluded_categories.0',
        'excluded_brands',
        'excluded_brands.0',
        'allowed_regions',
        'allowed_regions.0',
        'supported_currencies',
        'supported_currencies.0',
    ],
};

const webhookTab: AdditionalTab = {
    value: 'webhook',
    label: __('Webhook'),
    fields: ['sync_external_refunds'],
    content: (props) => <TapWebhookTab {...props} />,
};

interface TapProps {
    paymentGateway: PaymentGateway | undefined;
    onToggle: (paymentGateway: PaymentGateway | undefined, checked: boolean) => void;
}

export function Tap({ paymentGateway, onToggle }: TapProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const isActive = paymentGateway?.is_active ?? false;

    const handleToggle = (checked: boolean) => {
        onToggle(paymentGateway, checked);
    };

    return (
        <Card className="group/item h-38 shadow-xs md:h-45">
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center">
                    <TapLogo className="h-10" />
                </div>

                <div className="flex items-center justify-between">
                    <HoverActions>
                        <Button variant="ghost" size="icon" onClick={() => setDialogOpen(true)}>
                            <Settings2Icon />
                        </Button>
                    </HoverActions>

                    <PaymentGatewayDialog
                        open={dialogOpen}
                        onOpenChange={setDialogOpen}
                        title="Tap"
                        description={__('Configure Tap payment gateway')}
                        paymentGateway={paymentGateway}
                        gatewayDriver="tap"
                        tabFields={tabFields}
                        credentialFields={(props) => <TapCredentialsForm {...props} />}
                        additionalTabs={[webhookTab]}
                    />

                    {paymentGateway ? (
                        <Switch checked={isActive} onCheckedChange={handleToggle} />
                    ) : (
                        <HelpBlock>{__('Not configured')}</HelpBlock>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function TapLogo(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 161 68" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
            <path d="M75.747 4.686h-5.022v10.045h-.012v4.284h.012v19.687q0 2.879.199 4.554c.133 1.118.457 2.22.97 3.314q1.705 3.381 5.792 4.37 4.084.988 9.142-.05v-4.386c-2.79.445-5.019.504-6.68.168q-2.492-.502-3.666-2.646a5.25 5.25 0 0 1-.653-2.091q-.117-1.155-.082-3.33V19.019h11.08v-4.284s-7.327.115-11.08.115h-1.303c-.352 0-.954.048-.954-.095 0-.668 2.257-.19 2.257-1.05zM121.015 24.288q-.233-1.86-.872-3.366c-.961-2.321-2.587-4.1-4.889-5.339q-3.448-1.857-8.439-1.856-5.962 0-9.724 2.661-3.766 2.662-5.038 7.552l4.89 1.438q.971-3.548 3.498-5.241c1.684-1.126 3.775-1.693 6.277-1.693q3.546.002 5.675 1.122c1.419.747 2.431 1.888 3.045 3.416.543 1.352.824 3.037.856 5.062q-.693.091-1.408.18-5.659.737-8.938 1.223-3.282.485-6.094 1.188-4.32 1.137-6.746 3.717t-2.427 6.828c0 1.966.469 3.76 1.407 5.39q1.406 2.444 4.202 3.885 2.795 1.438 6.746 1.439 3.581 0 6.511-1.071c1.955-.716 3.643-1.779 5.074-3.198a14.6 14.6 0 0 0 2.142-2.716l.799-1.452c1.102.254-.799 1.637-.799 4.675v2.757h4.487V28.458q0-2.311-.235-4.167zm-4.854 11.065a17.6 17.6 0 0 1-.403 2.814q-.504 2.746-2.075 4.87c-1.048 1.419-2.435 2.52-4.151 3.314q-2.578 1.19-5.894 1.188-2.745 0-4.519-.903-1.775-.902-2.595-2.345a6.2 6.2 0 0 1-.82-3.115q-.001-2.678 1.739-4.201c1.16-1.017 2.646-1.779 4.452-2.295q2.578-.704 5.843-1.172c2.177-.313 4.882-.657 8.118-1.04l.398-.05a48 48 0 0 1-.097 2.93zM158.684 23.017c-1.282-2.888-3.126-5.16-5.522-6.812q-3.6-2.48-8.455-2.478c-3.236 0-6.132.816-8.489 2.442-3.467 2.45-3.481 4.406-4.101 4.406s.642-1.527.642-2.72c-.008-.62-.008-3.124-.008-3.124h-4.518v52.492h5.054V49.149c0-2.433-1.195-3.148-.598-3.34.598-.19 1.325 2.047 3.494 3.59q3.499 2.493 8.352 2.494c3.237 0 6.164-.824 8.587-2.478q3.636-2.479 5.558-6.828 1.925-4.35 1.927-9.81c0-3.64-.641-6.868-1.927-9.76zm-4.702 17.194c-.848 2.189-2.114 3.904-3.799 5.155q-2.527 1.876-6.175 1.876c-2.431 0-4.526-.613-6.211-1.84q-2.528-1.844-3.783-5.105-1.255-3.266-1.255-7.516c0-2.834.414-5.39 1.239-7.532q1.238-3.214 3.748-5.038 2.512-1.825 6.16-1.825c2.431 0 4.597.617 6.293 1.856q2.544 1.859 3.799 5.124t1.255 7.415q-.002 4.15-1.271 7.434zM27.72 4.687c12.929 0 23.45 10.521 23.45 23.45 0 12.93-10.521 23.452-23.45 23.452-12.93 0-23.452-10.522-23.452-23.451S14.79 4.687 27.72 4.687m0-3.909C12.608.778.36 13.028.36 28.138s12.249 27.36 27.36 27.36 27.36-12.25 27.36-27.36S42.83.778 27.72.778" />
            <path d="M27.719 12.503c8.622 0 15.634 7.012 15.634 15.635 0 8.622-7.012 15.634-15.634 15.634S12.085 36.76 12.085 28.138s7.012-15.635 15.634-15.635m0-3.908c-10.792 0-19.543 8.751-19.543 19.542S16.927 47.68 27.72 47.68s19.543-8.751 19.543-19.542S38.51 8.595 27.719 8.595" />
        </svg>
    );
}
