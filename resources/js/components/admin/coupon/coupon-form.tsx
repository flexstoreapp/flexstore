import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import { CouponFormAvailability } from '@/components/admin/coupon/coupon-form-availability';
import { CouponFormBasicInfo } from '@/components/admin/coupon/coupon-form-basic-info';
import { CouponFormRestrictions } from '@/components/admin/coupon/coupon-form-restrictions';
import { CouponShareLinkCard } from '@/components/admin/coupon/coupon-share-link-card';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { __ } from '@/lib/i18n';
import type { Coupon, CouponType } from '@/types';

export function CouponForm({ coupon }: { coupon?: Coupon }) {
    const [couponType, setCouponType] = useState<CouponType>(coupon?.type ?? 'flat');

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_active = data.is_active === 'on';
        data.add_more = data.add_more === 'on';
        return data;
    };

    return (
        <Form
            {...(coupon ? CouponController.update.form({ coupon: coupon.id }) : CouponController.store.form())}
            options={{ preserveScroll: true, only: ['coupon'] }}
            transform={handleTransform}
            resetOnSuccess={!coupon}
            setDefaultsOnSuccess
        >
            {({ processing, errors, recentlySuccessful }) => (
                <>
                    <UnsavedChangesAlert />
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <CouponFormBasicInfo
                                coupon={coupon}
                                couponType={couponType}
                                onChangeCouponType={setCouponType}
                                errors={errors}
                            />
                            <CouponFormRestrictions coupon={coupon} couponType={couponType} errors={errors} />
                        </div>

                        <div className="space-y-6">
                            <CouponFormAvailability coupon={coupon} errors={errors} />
                            {coupon && <CouponShareLinkCard coupon={coupon} />}
                            <FormSubmit
                                showAddMore={!coupon}
                                processing={processing}
                                recentlySuccessful={recentlySuccessful}
                            >
                                {coupon ? __('Update coupon') : __('Add coupon')}
                            </FormSubmit>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
