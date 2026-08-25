import { Link } from '@inertiajs/react';
import { memo, useMemo } from 'react';

import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import { StatusBadge } from '@/components/admin/status-badge';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { stripTrailingZeros } from '@/lib/utils';
import type { Coupon } from '@/types';

interface CouponRowProps {
    coupon: Coupon;
    isSelected: boolean;
    onSelectCoupon: (couponId: number | string, shiftKey?: boolean) => void;
}

export const CouponRow = memo(({ coupon, isSelected, onSelectCoupon }: CouponRowProps) => {
    const formatDate = useFormatDate();
    const { formatMoney } = useFormatMoney();
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.CouponsManage);
    const canDelete = hasPermission(Permission.CouponsDelete);
    const showCheckbox = canUpdate || canDelete;

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation({
        url: CouponController.edit(coupon).url,
        permission: Permission.CouponsManage,
    });

    const handleSelectCoupon = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectCoupon(coupon.id, e.shiftKey);
    };

    const tableRowClass = useMemo(() => {
        if (!canNavigate) return 'cursor-default';
        return showCheckbox ? '[&>td:not(:first-child)]:cursor-pointer' : 'cursor-pointer';
    }, [canNavigate, showCheckbox]);

    return (
        <TableRow
            key={coupon.id}
            data-state={isSelected && 'selected'}
            className={tableRowClass}
            onClick={handleRowClick}
        >
            <Can permission={Permission.CouponsDelete}>
                <TableCell onClick={handleSelectCoupon} className="w-10">
                    <Checkbox checked={isSelected} aria-label={__('Select :name', { name: coupon.code })} />
                </TableCell>
            </Can>
            <TableCell>
                <Can
                    permission={Permission.CouponsManage}
                    fallback={<span className="font-medium">{coupon.code}</span>}
                >
                    <Link
                        href={CouponController.edit(coupon)}
                        onClick={handleLinkClick}
                        className="font-medium underline-offset-4 hover:underline"
                        prefetch
                    >
                        {coupon.code}
                    </Link>
                </Can>
            </TableCell>
            <TableCell className="text-end">
                {coupon.type === 'percentage' ? `${stripTrailingZeros(coupon.value)}%` : formatMoney(coupon.value)}
            </TableCell>
            <TableCell className="text-end">
                {coupon.usage_limit ? `${coupon.used_count}/${coupon.usage_limit}` : coupon.used_count}
            </TableCell>
            <TableCell className="text-xs whitespace-nowrap text-muted-foreground">
                {coupon.starts_at && coupon.expires_at
                    ? `${formatDate(coupon.starts_at)} — ${formatDate(coupon.expires_at)}`
                    : coupon.starts_at
                      ? __('From :datetime', { datetime: formatDate(coupon.starts_at) })
                      : coupon.expires_at
                        ? __('Until :datetime', { datetime: formatDate(coupon.expires_at) })
                        : '—'}
            </TableCell>
            <TableCell>
                <StatusBadge status={coupon.is_active ? 'active' : 'inactive'}>
                    {coupon.is_active ? __('Active') : __('Inactive')}
                </StatusBadge>
            </TableCell>
        </TableRow>
    );
});
CouponRow.displayName = 'CouponRow';
