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
import { StripeCredentials } from './stripe-credentials';
import { StripeWebhookTab } from './stripe-webhook-tab';

const tabFields: Record<PaymentGatewayDialogTab, string[]> = {
    general: [
        'name',
        'credentials.publishable_key',
        'credentials.secret_key',
        'credentials.checkout_mode',
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
    fields: ['credentials.signing_secret', 'sync_external_refunds'],
    content: (props) => <StripeWebhookTab {...props} />,
};

interface StripeProps {
    paymentGateway: PaymentGateway | undefined;
    onToggle: (paymentGateway: PaymentGateway | undefined, checked: boolean) => void;
}

export function Stripe({ paymentGateway, onToggle }: StripeProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const isActive = paymentGateway?.is_active ?? false;

    const handleToggle = (checked: boolean) => {
        onToggle(paymentGateway, checked);
    };

    return (
        <Card className="group/item h-38 shadow-xs md:h-45">
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center">
                    <StripeLogo className="h-9" />
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
                        title="Stripe"
                        description={__('Configure Stripe payment gateway')}
                        paymentGateway={paymentGateway}
                        gatewayDriver="stripe"
                        tabFields={tabFields}
                        credentialFields={(props) => <StripeCredentials {...props} />}
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

function StripeLogo(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                fill="currentColor"
                d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48m-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58M40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6M40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84M28.24 5.57h4.13v14.44h-4.13zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86m-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1 0-1.98-6.26-1.24-6.26-5.8C0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.2 9.2 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.9 0 1.85 6.29.97 6.29 5.88z"
            />
        </svg>
    );
}
