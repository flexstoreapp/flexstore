import { useEffect, useRef } from 'react';

import { Button } from '@/components/storefront/button';
import { Spinner } from '@/components/storefront/spinner';
import { __ } from '@/lib/i18n';
import type { ListLoadingMethod } from '@/types';

interface LoadMoreProps {
    method: ListLoadingMethod;
    hasMore: boolean;
    loading: boolean;
    onLoadMore: () => void;
}

export function LoadMore({ method, hasMore, loading, onLoadMore }: LoadMoreProps) {
    const sentinelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (method !== 'infinite_scroll' || !hasMore || loading) {
            return;
        }

        const sentinel = sentinelRef.current;

        if (!sentinel) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    onLoadMore();
                }
            },
            { rootMargin: '400px' },
        );

        observer.observe(sentinel);

        return () => observer.disconnect();
    }, [method, hasMore, loading, onLoadMore]);

    if (!hasMore && !loading) {
        return null;
    }

    const spinner = <Spinner className="size-4.5 text-muted" />;

    return (
        <div className="mt-8 flex justify-center">
            {method === 'load_more' ? (
                <Button variant="outline" onClick={onLoadMore} disabled={loading}>
                    {loading && spinner}
                    {__('Load more')}
                </Button>
            ) : (
                <div ref={sentinelRef} className="flex h-10 items-center justify-center">
                    {loading && spinner}
                </div>
            )}
        </div>
    );
}
