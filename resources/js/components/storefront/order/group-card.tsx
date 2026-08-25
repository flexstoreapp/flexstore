import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { LineItem } from '@/components/storefront/line-item';
import { StatusBadge, type StatusTone } from '@/components/storefront/status-badge';
import { TrackingLink } from '@/components/storefront/tracking-link';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { OrderShipmentGroup } from '@/types';

function resolveBadge(group: OrderShipmentGroup): { label: string; tone: StatusTone } {
    return group.shipped ? { label: __('Shipped'), tone: 'info' } : { label: __('Processing'), tone: 'neutral' };
}

interface GroupCardProps {
    group: OrderShipmentGroup;
    index: number;
    total: number;
    currencyCode?: string;
}

export function GroupCard({ group, index, total, currencyCode }: GroupCardProps) {
    const badge = resolveBadge(group);
    const carrier = group.carrier_name;

    return (
        <section aria-label={__('Shipment :index of :total', { index, total })}>
            <div className="overflow-hidden rounded-md border border-line bg-surface">
                <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-b border-line bg-surface-2 px-6 py-4">
                    <div className="min-w-0 text-sm text-muted">
                        {group.refunded ? (
                            <span className="font-semibold text-ink">{__('Refunded')}</span>
                        ) : group.shipped ? (
                            <>
                                {carrier && <bdi className="font-semibold text-ink">{carrier}</bdi>}
                                {group.tracking_number && (
                                    <>
                                        {carrier && ' · '}
                                        <TrackingLink
                                            trackingNumber={group.tracking_number}
                                            trackingUrl={group.tracking_url}
                                        />
                                    </>
                                )}
                            </>
                        ) : group.digital ? (
                            <span className="font-semibold text-ink">{__('Digital items')}</span>
                        ) : (
                            <span className="font-semibold text-ink">{__('Not yet shipped')}</span>
                        )}
                    </div>
                    {!group.digital && !group.refunded && <StatusBadge tone={badge.tone}>{badge.label}</StatusBadge>}
                </div>

                <ul className="m-0 list-none divide-y divide-line px-6 py-0">
                    {group.items.map((item, itemIndex) => (
                        <LineItem
                            key={itemIndex}
                            title={getTranslation(item.product_title)}
                            variantTitle={item.variant_title}
                            url={item.url_handle ? ProductController.show(item.url_handle) : null}
                            media={item.featured_media}
                            quantity={item.quantity}
                            price={item.total_price}
                            currencyCode={currencyCode}
                            className="py-3.5 first:pt-4 last:pb-4"
                        />
                    ))}
                </ul>
            </div>
        </section>
    );
}
