import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import { CouponForm } from '@/components/admin/coupon/coupon-form';
import { Heading } from '@/components/admin/heading';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import type { Coupon } from '@/types';

export default function CouponEdit({ coupon }: { coupon: Coupon }) {
    const formatDate = useFormatDate();

    return (
        <>
            <Heading
                title={coupon.code}
                description={__('Last updated on :datetime', { datetime: formatDate(coupon.updated_at) })}
                backHref={CouponController.index()}
            />
            <CouponForm coupon={coupon} />
        </>
    );
}

CouponEdit.layout = ({ coupon }: { coupon: Coupon }) => ({
    breadcrumbs: [
        { title: __('Coupons'), href: CouponController.index() },
        { title: coupon.code, href: CouponController.edit(coupon) },
    ],
});
