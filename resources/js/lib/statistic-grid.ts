import { cn } from '@/lib/utils';

export const STAT_ROW_GRID: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-2 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-4',
    5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
    6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
    7: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-7',
    8: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-8',
};

export function statColsForRow(count: number): { mobile: number; sm: number; lg: number } {
    if (count <= 1) return { mobile: 1, sm: 1, lg: 1 };
    if (count <= 4) return { mobile: 2, sm: count, lg: count };
    if (count <= 6) return { mobile: 2, sm: 3, lg: count };
    return { mobile: 2, sm: 4, lg: count };
}

export function statCellClasses(index: number, mobileCols: number, smCols: number, lgCols: number): string {
    return cn(
        index % mobileCols === 0 ? 'ps-0' : 'border-s border-border',
        smCols !== mobileCols &&
            (index % smCols === 0 ? 'sm:border-s-0 sm:ps-0' : 'sm:border-s sm:border-border sm:ps-6'),
        lgCols !== smCols && (index % lgCols === 0 ? 'lg:border-s-0 lg:ps-0' : 'lg:border-s lg:border-border lg:ps-6'),
    );
}
