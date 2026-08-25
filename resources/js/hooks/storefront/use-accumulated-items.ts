import { useState } from 'react';

import type { Paginated, SimplePaginated } from '@/types';

/**
 * Accumulates paginated items across pages for load-more and infinite-scroll.
 * Pages arrive by replacing the paginator prop, so accumulation must read only
 * from the incoming prop — never by concatenating it with previously kept state.
 */
export function useAccumulatedItems<T>(page: Paginated<T> | SimplePaginated<T>, accumulate: boolean): T[] {
    const [items, setItems] = useState(page.data);
    const [seen, setSeen] = useState(page);

    if (page !== seen) {
        setSeen(page);
        setItems(accumulate && page.current_page > 1 ? [...items, ...page.data] : page.data);
    }

    return accumulate ? items : page.data;
}
