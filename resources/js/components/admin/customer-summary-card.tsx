import { Link } from '@inertiajs/react';

import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';

interface CustomerStats {
    name?: string | null;
    orders_count?: number;
    lifetime_value?: string;
    created_at: string;
}

interface AddressLike {
    first_name?: string | null;
    last_name?: string | null;
}

interface CustomerSummaryCardProps {
    customerId: number | null;
    email: string;
    billingAddress?: AddressLike | null;
    shippingAddress?: AddressLike | null;
    description?: string;
    stats?: CustomerStats | null;
}

function fullName(address?: AddressLike | null): string {
    if (!address) return '';
    return `${address.first_name ?? ''} ${address.last_name ?? ''}`.trim();
}

export function CustomerSummaryCard({
    customerId,
    email,
    billingAddress,
    shippingAddress,
    description,
    stats,
}: CustomerSummaryCardProps) {
    const displayName = stats?.name?.trim() || fullName(billingAddress) || fullName(shippingAddress) || __('Guest');
    const { formatMoney } = useFormatMoney();
    const formatDate = useFormatDate();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Customer')}</CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent className="space-y-1 text-sm tracking-wide">
                {customerId ? (
                    <Link href={CustomerController.edit(customerId)} className="font-medium hover:underline" prefetch>
                        {displayName}
                    </Link>
                ) : (
                    <p className="font-medium">{displayName}</p>
                )}

                <p className="text-muted-foreground">
                    <a href={`mailto:${email}`} className="hover:underline">
                        {email}
                    </a>
                </p>

                {stats && (
                    <>
                        <Separator className="my-3" />
                        <dl className="grid grid-cols-2 gap-y-2">
                            <dt className="text-muted-foreground">{__('Total orders')}</dt>
                            <dd className="text-end">{stats.orders_count ?? 0}</dd>
                            {stats.lifetime_value !== undefined && (
                                <>
                                    <dt className="text-muted-foreground">{__('Lifetime value')}</dt>
                                    <dd className="text-end">{formatMoney(stats.lifetime_value)}</dd>
                                </>
                            )}
                            <dt className="text-muted-foreground">{__('Customer since')}</dt>
                            <dd className="text-end">{formatDate(stats.created_at)}</dd>
                        </dl>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
