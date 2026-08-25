import { memo, useMemo } from 'react';

import { StatusBadge } from '@/components/admin/status-badge';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Brand } from '@/types';

interface BrandRowProps {
    brand: Brand;
    isSelected: boolean;
    onSelectBrand: (brandId: number, shiftKey?: boolean) => void;
    onEdit: (brand: Brand) => void;
}

export const BrandRow = memo(({ brand, isSelected, onSelectBrand, onEdit }: BrandRowProps) => {
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.BrandsManage);
    const canDelete = hasPermission(Permission.BrandsDelete);
    const showCheckbox = canUpdate || canDelete;

    const handleRowClick = () => {
        if (canUpdate) {
            onEdit(brand);
        }
    };

    const handleSelectBrand = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectBrand(brand.id, e.shiftKey);
    };

    const tableRowClass = useMemo(() => {
        if (!canUpdate) return 'cursor-default';
        return showCheckbox ? '[&>td:not(:first-child)]:cursor-pointer' : 'cursor-pointer';
    }, [canUpdate, showCheckbox]);

    return (
        <TableRow
            key={brand.id}
            data-state={isSelected && 'selected'}
            className={tableRowClass}
            onClick={handleRowClick}
        >
            <Can permissions={[Permission.BrandsManage, Permission.BrandsDelete]}>
                <TableCell onClick={handleSelectBrand} className="w-10">
                    <Checkbox
                        checked={isSelected}
                        aria-label={__('Select :name', { name: getTranslation(brand.name) })}
                    />
                </TableCell>
            </Can>

            <TableCell className="font-medium">{getTranslation(brand.name)}</TableCell>
            <TableCell className="text-end">{brand.products_count ?? 0}</TableCell>
            <TableCell>
                <StatusBadge status={brand.is_active ? 'active' : 'inactive'}>
                    {brand.is_active ? __('Active') : __('Inactive')}
                </StatusBadge>
            </TableCell>
        </TableRow>
    );
});
BrandRow.displayName = 'BrandRow';
