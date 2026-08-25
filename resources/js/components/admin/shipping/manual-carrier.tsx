import { router } from '@inertiajs/react';
import { Settings2Icon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import * as ShippingCarrierController from '@/actions/App/Http/Controllers/Admin/ShippingCarrierController';
import { useConfirm } from '@/components/admin/confirm';
import { HoverActions } from '@/components/admin/hover-actions';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ShippingCarrier } from '@/types';

import { ShippingCarrierDialog } from './shipping-carrier-dialog';

interface ManualCarrierProps {
    shippingCarrier: ShippingCarrier;
    onToggle: (shippingCarrier: ShippingCarrier, checked: boolean) => void;
}

export function ManualCarrier({ shippingCarrier, onToggle }: ManualCarrierProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const { confirm } = useConfirm();

    const handleDelete = () => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this shipping carrier.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(ShippingCarrierController.destroy(shippingCarrier), {
                        preserveScroll: true,
                        only: ['shippingCarriers'],
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    return (
        <>
            <Card className="group/item h-38 shadow-xs md:h-45">
                <CardContent className="flex h-full flex-col">
                    <div className="flex flex-1 items-center justify-center font-[Arial] text-xl font-semibold">
                        {getTranslation(shippingCarrier.name)}
                    </div>

                    <div className="flex items-center justify-between">
                        <HoverActions>
                            <Button variant="ghost" size="icon" onClick={() => setDialogOpen(true)}>
                                <Settings2Icon />
                            </Button>
                            <Button variant="ghost" size="icon" onClick={handleDelete}>
                                <Trash2Icon />
                                <span className="sr-only">{__('Delete shipping carrier')}</span>
                            </Button>
                        </HoverActions>

                        <Switch
                            checked={shippingCarrier.is_active}
                            onCheckedChange={(checked) => onToggle(shippingCarrier, checked)}
                        />
                    </div>
                </CardContent>
            </Card>

            <ShippingCarrierDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                title={getTranslation(shippingCarrier.name)}
                description={__('Configure :carrier shipping carrier', {
                    carrier: getTranslation(shippingCarrier.name),
                })}
                shippingCarrier={shippingCarrier}
            />
        </>
    );
}
