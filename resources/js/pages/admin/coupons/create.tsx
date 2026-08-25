import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import { CouponForm } from '@/components/admin/coupon/coupon-form';
import { Heading } from '@/components/admin/heading';
import { __ } from '@/lib/i18n';

export default function CouponCreate() {
    return (
        <>
            <Heading
                title={__('Add coupon')}
                description={__('Add a new discount coupon to your store')}
                backHref={CouponController.index()}
            />
            <CouponForm />
        </>
    );
}

CouponCreate.layout = {
    breadcrumbs: [
        { title: __('Coupons'), href: CouponController.index() },
        { title: __('Add coupon'), href: CouponController.create() },
    ],
};
