import { DownloadItem } from '@/components/storefront/account/download-item';
import { __ } from '@/lib/i18n';
import type { OrderItemDownload } from '@/types';

export function OrderDownloadsCard({ downloads }: { downloads: OrderItemDownload[] }) {
    if (downloads.length === 0) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-md border border-line bg-surface">
            <div className="border-b border-line px-6 py-4">
                <span className="text-sm font-semibold text-ink">{__('Downloads')}</span>
            </div>
            <ul className="m-0 list-none divide-y divide-line p-0">
                {downloads.map((download) => (
                    <li key={download.id} className="px-6 py-4">
                        <DownloadItem download={download} buttonVariant="outline" />
                    </li>
                ))}
            </ul>
        </div>
    );
}
