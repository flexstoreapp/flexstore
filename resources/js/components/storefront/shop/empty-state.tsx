import { __ } from '@/lib/i18n';

export function EmptyState() {
    return (
        <div className="mt-5 rounded-md border border-line bg-surface py-20 text-center">
            <div className="font-head text-3xl font-semibold text-ink">{__('No results found')}</div>
            <p className="mt-2 mb-0 text-muted">{__('Try adjusting your search or removing some filters.')}</p>
        </div>
    );
}
