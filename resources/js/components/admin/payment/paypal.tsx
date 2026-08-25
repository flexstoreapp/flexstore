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
import { PaypalCredentialsForm } from './paypal-credentials';
import { PaypalWebhookTab } from './paypal-webhook-tab';

const tabFields: Record<PaymentGatewayDialogTab, string[]> = {
    general: [
        'name',
        'credentials.client_id',
        'credentials.client_secret',
        'credentials.checkout_mode',
        'credentials.sandbox',
        'is_active',
    ],
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
    fields: ['credentials.webhook_id', 'sync_external_refunds'],
    content: (props) => <PaypalWebhookTab {...props} />,
};

interface PaypalProps {
    paymentGateway: PaymentGateway | undefined;
    onToggle: (paymentGateway: PaymentGateway | undefined, checked: boolean) => void;
}

export function Paypal({ paymentGateway, onToggle }: PaypalProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const isActive = paymentGateway?.is_active ?? false;

    const handleToggle = (checked: boolean) => {
        onToggle(paymentGateway, checked);
    };

    return (
        <Card className="group/item h-38 shadow-xs md:h-45">
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center">
                    <PaypalLogo className="h-9" />
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
                        title="PayPal"
                        description={__('Configure PayPal payment gateway')}
                        paymentGateway={paymentGateway}
                        gatewayDriver="paypal"
                        tabFields={tabFields}
                        credentialFields={(props) => <PaypalCredentialsForm {...props} />}
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

function PaypalLogo(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="77 150 388 96" xmlns="http://www.w3.org/2000/svg">
            <path
                fill="currentColor"
                d="M375 173h-21.1c-1.4 0-2.7 1-2.9 2.5l-8.5 53.5c-.1.5.1 1 .4 1.4.3.4.8.6 1.3.6H355c1 0 1.9-.7 2-1.7l2.4-15.2c.2-1.4 1.5-2.5 2.9-2.4h6.7c13.9 0 21.9-6.7 24-19.8.9-5.8 0-10.3-2.7-13.5-2.9-3.5-8.2-5.4-15.3-5.4zm2.4 19.6c-1.2 7.5-6.9 7.5-12.5 7.5h-3.2l2.2-14c.1-.9.9-1.5 1.7-1.5h1.5c3.8 0 7.4 0 9.3 2.1 1.1 1.3 1.5 3.2 1 5.9zm-153-19.6h-21.1c-1.4 0-2.7 1-2.9 2.5l-8.6 53.5c-.1.5.1 1 .4 1.4.3.4.8.6 1.3.6h10.1c1.4 0 2.7-1 2.9-2.4l2.3-14.4c.2-1.4 1.5-2.5 2.9-2.4h6.7c13.9 0 21.9-6.7 24-19.8.9-5.8 0-10.3-2.7-13.5-2.9-3.6-8.2-5.5-15.3-5.5zm2.5 19.6c-1.2 7.5-6.9 7.5-12.5 7.5h-3.2l2.2-14c.1-.9.9-1.5 1.7-1.5h1.5c3.8 0 7.4 0 9.3 2.1 1 1.3 1.4 3.2 1 5.9zm60.6-.3h-10.1c-.9 0-1.6.6-1.7 1.5l-.4 2.8-.7-1c-2.2-3.1-7.1-4.2-11.9-4.2-11.2 0-20.7 8.4-22.6 20.1-1 5.9.4 11.4 3.8 15.3 3.1 3.6 7.5 5.1 12.7 5.1 5.2.1 10.3-2 14-5.7l-.6 2.8c-.1.5.1 1 .4 1.4.3.4.8.6 1.3.6h9.1c1.4 0 2.7-1 2.9-2.4l5.5-34.2c.1-.5-.1-1-.4-1.4s-.8-.7-1.3-.7zm-14.1 19.5c-.9 5.6-5.7 9.7-11.4 9.5-2.6.2-5-.8-6.8-2.7-1.5-2-2.1-4.5-1.6-7 .8-5.6 5.7-9.7 11.3-9.6 2.5-.2 5 .8 6.7 2.7 1.8 2 2.4 4.6 1.8 7.1zm164.7-19.5H428c-.9 0-1.6.6-1.7 1.5l-.4 2.8-.7-1c-2.2-3.1-7.1-4.2-11.9-4.2-11.2 0-20.7 8.4-22.6 20.1-1 5.9.4 11.4 3.8 15.3 3.1 3.6 7.5 5.1 12.7 5.1 5.2.1 10.3-2 14-5.7l-.4 2.8c-.1.5.1 1 .4 1.4.3.4.8.6 1.3.6h9.1c1.4 0 2.7-1.1 2.9-2.5l5.5-34.2c.1-.5-.1-1-.4-1.4-.5-.4-1-.6-1.5-.6zM424 211.8c-.9 5.6-5.7 9.7-11.4 9.5-2.6.2-5-.8-6.8-2.7-1.5-2-2.1-4.5-1.6-7 .8-5.6 5.7-9.7 11.3-9.6 2.5-.2 5 .8 6.7 2.7 1.8 2 2.4 4.6 1.8 7.1zm-82.6-19.5h-10.2c-1 0-1.9.5-2.4 1.3l-14 20.4-5.9-19.6c-.4-1.2-1.5-2.1-2.8-2.1h-10c-.6 0-1.1.3-1.4.7-.3.5-.4 1-.2 1.6l11.2 32.4-10.7 14.7c-.4.5-.4 1.2-.1 1.8.3.6.9.9 1.6.9h10.2c1 0 1.9-.5 2.4-1.2l33.8-48.2c.4-.5.4-1.2.1-1.8-.4-.5-1-.9-1.6-.9zM450 174.5l-8.7 54.4c-.1.5.1 1 .4 1.4.3.4.8.6 1.3.6h8.7c1.4 0 2.7-1 2.9-2.4l8.6-53.4c.1-.5-.1-1-.4-1.4-.3-.4-.8-.6-1.3-.6h-9.8c-.8-.1-1.5.5-1.7 1.4zM110.2 171.6c.7-.4 1.6-.5 2.4-.5h22.7c2.6 0 5.2.2 7.8.6.7.1 1.3.2 2 .4.7.1 1.3.3 1.9.5l.9.3.9.3c.5-6.1-1.3-10.4-4.3-13.7-4.4-4.8-12.1-6.9-22.1-6.9h-27c-2 0-3.8 1.5-4.1 3.4l-12 75.6c-.1.7.1 1.4.6 2s1.2.9 1.9.8h15.9l9.4-58.6c.3-1.9 1.4-3.5 3.1-4.2z"
            />
            <path
                fill="currentColor"
                d="m146.7 176.3-.8-.2c-.5-.2-1.1-.3-1.7-.4l-1.8-.3c-2.4-.4-4.8-.5-7.2-.5h-21.1c-.5 0-1.1.1-1.6.3-1.1.5-1.8 1.5-2 2.7l-3.8 23.9c.4-.1.9-.1 1.3-.1h8.5c4.1.1 8.1-.5 12.1-1.5 9-2.6 15-8.3 18.1-17.5.6-1.9 1.1-3.8 1.5-5.7-.4-.3-1-.5-1.5-.7z"
            />
            <path
                fill="currentColor"
                d="M154.5 180.7c-.8-.8-1.6-1.5-2.6-2.1-2.3 11.2-9.4 27-35.6 27h-6.5c-2 0-3.8 1.5-4.1 3.4 0 0-5.1 31.5-5.3 33.1-.1.6.1 1.2.5 1.7s1 .7 1.6.7h13.1c1.8 0 3.3-1.3 3.6-3l3.2-19.5c.3-1.7 1.8-3 3.6-3h2.2c14.6 0 26-5.9 29.4-22.8l.3-1.5c.6-5.8-.3-10.5-3.4-14z"
            />
        </svg>
    );
}
