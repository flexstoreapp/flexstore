import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

import * as ProductQuickViewController from '@/actions/App/Http/Controllers/Storefront/ProductQuickViewController';
import { QuickViewModal } from '@/components/storefront/quick-view-modal';
import type { ProductBuyBoxData } from '@/types';

type QuickViewStatus = 'idle' | 'loading' | 'loaded' | 'error';

interface QuickViewContextValue {
    open: (urlHandle: string) => void;
}

const QuickViewContext = createContext<QuickViewContextValue | null>(null);

export function useQuickView(): QuickViewContextValue {
    const context = useContext(QuickViewContext);
    if (context === null) {
        throw new Error('useQuickView must be used within a QuickViewProvider');
    }
    return context;
}

export function QuickViewProvider({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const [status, setStatus] = useState<QuickViewStatus>('idle');
    const [data, setData] = useState<ProductBuyBoxData | null>(null);

    const open = useCallback((urlHandle: string) => {
        setIsOpen(true);
        setStatus('loading');
        setData(null);

        fetch(ProductQuickViewController.show.url({ product: urlHandle }), {
            headers: { Accept: 'application/json' },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to load');
                }
                return response.json() as Promise<ProductBuyBoxData>;
            })
            .then((result) => {
                setData(result);
                setStatus('loaded');
            })
            .catch(() => setStatus('error'));
    }, []);

    const close = useCallback(() => setIsOpen(false), []);

    return (
        <QuickViewContext.Provider value={{ open }}>
            {children}
            <QuickViewModal open={isOpen} status={status} data={data} onClose={close} />
        </QuickViewContext.Provider>
    );
}
