import { Button } from '@/components/ui/button';
import { __, transChoice } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface TablePaginationProps extends React.HTMLAttributes<HTMLDivElement> {
    from: number;
    to: number;
    total: number;
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}

export function TablePagination({
    from,
    to,
    total,
    currentPage,
    lastPage,
    onPageChange,
    className,
    ...props
}: TablePaginationProps) {
    return (
        <div className={cn('flex items-center justify-between', className)} {...props}>
            <div className="text-sm font-normal text-muted-foreground">
                {transChoice('Showing :from to :to of :count row|Showing :from to :to of :count rows', total, {
                    from,
                    to,
                    count: total,
                })}
            </div>
            <div className="flex gap-2">
                {currentPage > 1 && (
                    <Button variant="outline" onClick={() => onPageChange(currentPage - 1)}>
                        {__('Previous')}
                    </Button>
                )}
                {currentPage < lastPage && (
                    <Button variant="outline" onClick={() => onPageChange(currentPage + 1)}>
                        {__('Next')}
                    </Button>
                )}
            </div>
        </div>
    );
}
