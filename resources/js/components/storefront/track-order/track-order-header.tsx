import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import type { TrackedOrderData } from '@/types';

export function TrackOrderHeader({ order }: { order: TrackedOrderData }) {
    const formatId = useFormatId();
    const formatDate = useFormatDate();

    return (
        <div className="rounded-md border border-line bg-surface p-6">
            <h2 className="m-0 text-2xl font-semibold text-ink">
                {__('Order :number', { number: formatId(order.id) })}
            </h2>
            <p className="mt-1 mb-0 text-sm text-muted">
                {__('Placed :date', {
                    date: formatDate(order.created_at, { hour: undefined, minute: undefined }),
                })}
            </p>
        </div>
    );
}
