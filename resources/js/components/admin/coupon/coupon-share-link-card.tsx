import { usePage } from '@inertiajs/react';

import DiscountLinkController from '@/actions/App/Http/Controllers/Storefront/DiscountLinkController';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CopyableInput } from '@/components/ui/copyable-input';
import { __ } from '@/lib/i18n';
import type { AdminSharedData, Coupon } from '@/types';

export function CouponShareLinkCard({ coupon }: { coupon: Coupon }) {
    const { appUrl } = usePage<AdminSharedData>().props;
    const shareUrl = `${appUrl}${DiscountLinkController.url({ code: coupon.code })}`;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Shareable link')}</CardTitle>
                <CardDescription>{__('Discount applies automatically at checkout')}</CardDescription>
            </CardHeader>
            <CardContent>
                <CopyableInput value={shareUrl} />
            </CardContent>
        </Card>
    );
}
