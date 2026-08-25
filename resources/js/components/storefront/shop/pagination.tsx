import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPage: (page: number) => void;
}

function pageWindow(current: number, last: number): (number | 'gap')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages = new Set<number>([1, last, current, current - 1, current + 1]);
    const sorted = [...pages].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

    const result: (number | 'gap')[] = [];
    let previous = 0;

    for (const page of sorted) {
        if (page - previous > 1) {
            result.push('gap');
        }
        result.push(page);
        previous = page;
    }

    return result;
}

export function Pagination({ currentPage, lastPage, onPage }: PaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    const stepClass =
        'flex h-10 w-10 items-center justify-center rounded-sm border border-line-strong text-muted transition-colors hover:border-primary hover:text-primary disabled:opacity-40 disabled:hover:border-line-strong disabled:hover:text-muted';

    return (
        <nav aria-label={__('Pagination')} className="mt-8 flex items-center justify-center gap-2">
            <button
                type="button"
                onClick={() => onPage(currentPage - 1)}
                disabled={currentPage <= 1}
                aria-label={__('Previous page')}
                className={stepClass}
            >
                <ChevronLeftIcon size={15} strokeWidth={2} aria-hidden="true" className="rtl:rotate-180" />
            </button>

            {pageWindow(currentPage, lastPage).map((page, index) =>
                page === 'gap' ? (
                    <span
                        key={`gap-${index}`}
                        aria-hidden="true"
                        className="flex h-10 w-6 items-center justify-center text-muted"
                    >
                        ...
                    </span>
                ) : (
                    <button
                        key={page}
                        type="button"
                        onClick={() => onPage(page)}
                        aria-label={__('Page :number', { number: page })}
                        aria-current={page === currentPage ? 'page' : undefined}
                        className={cn(
                            'flex h-10 w-10 items-center justify-center rounded-sm font-semibold',
                            page === currentPage
                                ? 'bg-primary text-white'
                                : 'border border-line-strong text-muted transition-colors hover:border-primary hover:text-primary',
                        )}
                    >
                        {page}
                    </button>
                ),
            )}

            <button
                type="button"
                onClick={() => onPage(currentPage + 1)}
                disabled={currentPage >= lastPage}
                aria-label={__('Next page')}
                className={stepClass}
            >
                <ChevronRightIcon size={15} strokeWidth={2} aria-hidden="true" className="rtl:rotate-180" />
            </button>
        </nav>
    );
}
