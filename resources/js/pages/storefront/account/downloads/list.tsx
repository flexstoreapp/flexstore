import { Link, router } from '@inertiajs/react';

import * as DownloadController from '@/actions/App/Http/Controllers/Storefront/DownloadController';
import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import { DownloadItem } from '@/components/storefront/account/download-item';
import { DownloadsEmptyState } from '@/components/storefront/account/downloads-empty-state';
import { Pagination } from '@/components/storefront/shop/pagination';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import type { AccountDownload, Paginated } from '@/types';

export default function Downloads({ downloads }: { downloads: Paginated<AccountDownload> }) {
    const formatDate = useFormatDate();
    const formatId = useFormatId();

    if (downloads.data.length === 0) {
        return <DownloadsEmptyState />;
    }

    return (
        <>
            <ul className="m-0 flex list-none flex-col gap-4 p-0">
                {downloads.data.map((download) => (
                    <li key={download.id} className="rounded-md border border-line bg-surface p-4">
                        <DownloadItem
                            download={download}
                            meta={
                                <>
                                    {' · '}
                                    {__('Purchased :date', {
                                        date: formatDate((download.order ?? download).created_at, {
                                            hour: undefined,
                                            minute: undefined,
                                        }),
                                    })}
                                    {download.order && (
                                        <>
                                            {' · '}
                                            <Link
                                                href={OrderController.show(download.order.id)}
                                                className="text-primary underline-offset-2 hover:underline"
                                            >
                                                {formatId(download.order.id)}
                                            </Link>
                                        </>
                                    )}
                                </>
                            }
                        />
                    </li>
                ))}
            </ul>

            <Pagination
                currentPage={downloads.current_page}
                lastPage={downloads.last_page}
                onPage={(page) =>
                    router.get(
                        DownloadController.index().url,
                        { page },
                        { preserveScroll: true, preserveState: true, only: ['downloads'] },
                    )
                }
            />
        </>
    );
}

Downloads.title = __('Downloads');
