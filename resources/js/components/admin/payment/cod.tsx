import { Settings2Icon } from 'lucide-react';
import { useState } from 'react';

import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { HelpBlock } from '@/components/ui/help-block';
import { Switch } from '@/components/ui/switch';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { PaymentGatewayDialog, type PaymentGatewayDialogTab } from './payment-gateway-dialog';

const tabFields: Record<PaymentGatewayDialogTab, string[]> = {
    general: ['name'],
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

interface CodProps {
    paymentGateway: PaymentGateway | undefined;
    onToggle: (paymentGateway: PaymentGateway | undefined, checked: boolean) => void;
}

export function Cod({ paymentGateway, onToggle }: CodProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const isActive = paymentGateway?.is_active ?? false;

    const handleToggle = (checked: boolean) => {
        onToggle(paymentGateway, checked);
    };

    return (
        <Card className="group/item h-38 shadow-xs md:h-45">
            <CardContent className="flex h-full flex-col">
                <div className="flex flex-1 items-center justify-center">
                    <div className="font-[Arial]">
                        <p className="text-md">{__('Cash on')}</p>
                        <p className="text-3xl leading-none font-semibold">{__('Delivery')}</p>
                    </div>
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
                        title={__('Cash on Delivery')}
                        description={__('Configure Cash on Delivery payment gateway')}
                        paymentGateway={paymentGateway}
                        gatewayDriver="cod"
                        tabFields={tabFields}
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
