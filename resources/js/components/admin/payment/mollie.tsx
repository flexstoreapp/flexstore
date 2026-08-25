import { Settings2Icon } from 'lucide-react';
import { useState } from 'react';

import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { HelpBlock } from '@/components/ui/help-block';
import { Switch } from '@/components/ui/switch';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { MollieCredentialsForm } from './mollie-credentials';
import { MollieWebhookTab } from './mollie-webhook-tab';
import { PaymentGatewayDialog, type AdditionalTab, type PaymentGatewayDialogTab } from './payment-gateway-dialog';

const tabFields: Record<PaymentGatewayDialogTab, string[]> = {
    general: ['name', 'credentials.api_key', 'credentials.profile_id', 'credentials.checkout_mode', 'is_active'],
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
    content: (props) => <MollieWebhookTab {...props} />,
};

interface MollieProps {
    paymentGateway: PaymentGateway | undefined;
    onToggle: (paymentGateway: PaymentGateway | undefined, checked: boolean) => void;
}

export function Mollie({ paymentGateway, onToggle }: MollieProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const isActive = paymentGateway?.is_active ?? false;

    const handleToggle = (checked: boolean) => {
        onToggle(paymentGateway, checked);
    };

    return (
        <Card className="group/item h-38 shadow-xs md:h-45">
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center">
                    <MollieLogo className="h-9" />
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
                        title="Mollie"
                        description={__('Configure Mollie payment gateway')}
                        paymentGateway={paymentGateway}
                        gatewayDriver="mollie"
                        tabFields={tabFields}
                        credentialFields={(props) => <MollieCredentialsForm {...props} />}
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

function MollieLogo(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 320 94" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M289.3 44.3c6.9 0 13.2 4.5 15.4 11H274c2.1-6.4 8.3-11 15.3-11M320 60.9c0-8-3.1-15.6-8.8-21.4s-13.3-9-21.3-9h-.4c-8.3.1-16.2 3.4-22.1 9.3s-9.2 13.7-9.3 22c-.1 8.5 3.2 16.5 9.2 22.6 6.1 6.1 14.1 9.5 22.6 9.5 11.2 0 21.7-6 27.4-15.6l.7-1.2-12.6-6.2-.6 1c-3.1 5.2-8.6 8.2-14.7 8.2-7.7 0-14.4-5.1-16.5-12.5H320zm-78.8-41.1c-5.5 0-9.9-4.4-9.9-9.9s4.4-9.9 9.9-9.9 9.9 4.4 9.9 9.9c.1 5.4-4.4 9.9-9.9 9.9m-7.6 72.9h15.2V31.8h-15.2zM204.5 1.3h15.2v91.5h-15.2zm-29.1 91.4h15.2V1.3h-15.2zM135.3 79c-9.2 0-16.8-7.5-16.8-16.7s7.5-16.7 16.8-16.7 16.8 7.5 16.8 16.7S144.6 79 135.3 79m0-48.5c-17.6 0-31.8 14.2-31.8 31.7S117.8 94 135.3 94s31.8-14.2 31.8-31.7-14.2-31.8-31.8-31.8m-64.9.1c-.8-.1-1.6-.1-2.4-.1-7.7 0-15 3.1-20.2 8.7-5.2-5.5-12.5-8.7-20.1-8.7C12.4 30.5 0 42.9 0 58v34.7h14.9V58.5c0-6.3 5.2-12.1 11.3-12.7.4 0 .9-.1 1.3-.1 6.9 0 12.5 5.6 12.5 12.5v34.6h15.2V58.4c0-6.3 5.2-12.1 11.3-12.7.4 0 .9-.1 1.3-.1 6.9 0 12.5 5.6 12.6 12.4v34.7h15.2V58.5c0-7-2.6-13.6-7.2-18.8-4.7-5.3-11.1-8.5-18-9.1"
            />
        </svg>
    );
}
