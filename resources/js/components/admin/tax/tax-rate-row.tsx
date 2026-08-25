import { router } from '@inertiajs/react';
import { Trash2Icon } from 'lucide-react';

import * as TaxRateController from '@/actions/App/Http/Controllers/Admin/TaxRateController';
import { useConfirm } from '@/components/admin/confirm';
import { HoverActions } from '@/components/admin/hover-actions';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { __ } from '@/lib/i18n';
import { getTranslation, stripTrailingZeros } from '@/lib/utils';
import type { TaxCategoryOption, TaxRate } from '@/types';

interface TaxRateRowProps {
    taxRate: TaxRate;
    taxCategories: TaxCategoryOption[];
    onEdit: (taxRate: TaxRate) => void;
}

export function TaxRateRow({ taxRate, taxCategories, onEdit }: TaxRateRowProps) {
    const { confirm } = useConfirm();

    const handleDelete = (e: React.MouseEvent, taxRate: TaxRate) => {
        e.stopPropagation();

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this tax rate.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(TaxRateController.destroy(taxRate), {
                        preserveScroll: true,
                        only: ['taxRates'],
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    return (
        <TableRow className="group/item cursor-pointer" onClick={() => onEdit(taxRate)}>
            <TableCell>
                <div className="flex items-center gap-x-2">
                    <span className="font-medium">{getTranslation(taxRate.name)}</span>
                    {taxRate.is_compound && (
                        <Badge variant="outline" className="font-normal text-muted-foreground">
                            {__('Compound')}
                        </Badge>
                    )}
                </div>
            </TableCell>
            <TableCell className="text-end">{`${stripTrailingZeros(taxRate.rate)}%`}</TableCell>
            <TableCell>{getTranslation(taxRate.region?.name)}</TableCell>
            <TableCell>
                {taxRate.tax_category ? (
                    (taxCategories.find((option) => option.value === taxRate.tax_category)?.label ??
                    taxRate.tax_category)
                ) : (
                    <span className="text-muted-foreground">{__('All categories')}</span>
                )}
            </TableCell>
            <TableCell>
                <StatusBadge status={taxRate.is_active ? 'active' : 'inactive'}>
                    {taxRate.is_active ? __('Active') : __('Inactive')}
                </StatusBadge>
            </TableCell>
            <TableCell>
                <HoverActions>
                    <Button variant="ghost" size="icon-md" onClick={(e) => handleDelete(e, taxRate)}>
                        <Trash2Icon />
                        <span className="sr-only">{__('Delete tax rate')}</span>
                    </Button>
                </HoverActions>
            </TableCell>
        </TableRow>
    );
}
