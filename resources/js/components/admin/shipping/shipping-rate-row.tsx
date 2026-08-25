import { router } from '@inertiajs/react';
import { Trash2Icon } from 'lucide-react';

import * as ShippingRateController from '@/actions/App/Http/Controllers/Admin/ShippingRateController';
import { useConfirm } from '@/components/admin/confirm';
import { HoverActions } from '@/components/admin/hover-actions';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ShippingRate } from '@/types';

interface ShippingRateRowProps {
    shippingRate: ShippingRate;
    carrierName: string;
    onEdit: (shippingRate: ShippingRate) => void;
}

export function ShippingRateRow({ shippingRate, carrierName, onEdit }: ShippingRateRowProps) {
    const { formatMoney } = useFormatMoney();
    const { confirm } = useConfirm();

    const handleDelete = (e: React.MouseEvent, shippingRateToDelete: ShippingRate) => {
        e.stopPropagation();

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this shipping rate.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(ShippingRateController.destroy(shippingRateToDelete), {
                        preserveScroll: true,
                        only: ['shippingRates'],
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    return (
        <TableRow className="group/item cursor-pointer" onClick={() => onEdit(shippingRate)}>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span className="font-medium">{getTranslation(shippingRate.name)}</span>
                    {shippingRate.type === 'live' ? (
                        <span className="text-muted-foreground">{__('Estimated by carrier')}</span>
                    ) : (
                        shippingRate.delivery_time && (
                            <span className="text-muted-foreground">{getTranslation(shippingRate.delivery_time)}</span>
                        )
                    )}
                </div>
            </TableCell>
            <TableCell className="text-end">
                {shippingRate.type === 'free' ? (
                    <span className="text-muted-foreground">{__('Free shipping')}</span>
                ) : shippingRate.type === 'live' ? (
                    <span className="text-muted-foreground">{__('Calculated at checkout')}</span>
                ) : (
                    formatMoney(shippingRate.rate!)
                )}
            </TableCell>
            <TableCell>{getTranslation(shippingRate.region?.name)}</TableCell>
            <TableCell>{carrierName}</TableCell>
            <TableCell>
                <StatusBadge status={shippingRate.is_active ? 'active' : 'inactive'}>
                    {shippingRate.is_active ? __('Active') : __('Inactive')}
                </StatusBadge>
            </TableCell>
            <TableCell>
                <HoverActions>
                    <Button variant="ghost" size="icon-md" onClick={(e) => handleDelete(e, shippingRate)}>
                        <Trash2Icon />
                        <span className="sr-only">{__('Delete shipping rate')}</span>
                    </Button>
                </HoverActions>
            </TableCell>
        </TableRow>
    );
}
