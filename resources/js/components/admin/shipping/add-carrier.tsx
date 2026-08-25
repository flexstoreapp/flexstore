import { PlusIcon } from 'lucide-react';
import { useState } from 'react';

import { ShippingCarrierDialog } from '@/components/admin/shipping/shipping-carrier-dialog';
import { Card, CardContent } from '@/components/ui/card';
import { __ } from '@/lib/i18n';

export function AddCarrier() {
    const [dialogOpen, setDialogOpen] = useState(false);

    return (
        <>
            <Card
                className="h-38 cursor-pointer border border-dashed text-muted-foreground shadow-xs transition-colors duration-150 hover:bg-accent/50 md:h-45"
                onClick={() => setDialogOpen(true)}
            >
                <CardContent className="flex flex-1 flex-col items-center justify-center space-y-2 text-center">
                    <div className="flex size-12 items-center justify-center rounded-full bg-accent text-muted-foreground">
                        <PlusIcon />
                    </div>
                    <p className="text-sm">{__('Add carrier')}</p>
                </CardContent>
            </Card>

            <ShippingCarrierDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                title={__('Add carrier')}
                description={__('Add a new shipping carrier to your store')}
            />
        </>
    );
}
