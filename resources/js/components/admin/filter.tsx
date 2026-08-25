import { FilterIcon, XIcon } from 'lucide-react';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface FilterButtonProps {
    showFilters: boolean;
    setShowFilters: (value: boolean) => void;
    filters: Record<string, unknown>;
    onResetFilters: () => void;
}

export function FilterButton({ showFilters, setShowFilters, filters, onResetFilters }: FilterButtonProps) {
    const activeFilterCount = Object.entries(filters).filter(([key, value]) => {
        if (['category_name', 'sort', 'direction'].includes(key)) {
            return false;
        }

        return value !== '' && value !== null && value !== undefined;
    }).length;

    return (
        <div className="flex items-center gap-2">
            <Button
                variant="outline"
                className="relative flex items-center gap-1"
                onClick={() => setShowFilters(!showFilters)}
            >
                <FilterIcon className="-ms-0.5 size-3.5" />
                {__('Filters')}
                {activeFilterCount > 0 && (
                    <Badge
                        variant="secondary"
                        className="ms-1 flex h-5 min-w-5 items-center justify-center px-1 text-xs"
                    >
                        {activeFilterCount}
                    </Badge>
                )}
            </Button>

            {activeFilterCount > 0 && (
                <Button variant="ghost" size="icon" onClick={onResetFilters}>
                    <XIcon />
                </Button>
            )}
        </div>
    );
}

interface BooleanFilterSelectProps {
    value: boolean | null;
    onChange: (value: boolean | null) => void;
    allLabel: string;
    trueLabel: string;
    falseLabel?: string;
    'aria-label'?: string;
}

export function BooleanFilterSelect({
    value,
    onChange,
    allLabel,
    trueLabel,
    falseLabel,
    'aria-label': ariaLabel,
}: BooleanFilterSelectProps) {
    const hasFalseOption = falseLabel !== undefined;

    const options = [
        { value: 'true', label: trueLabel },
        ...(hasFalseOption ? [{ value: 'false', label: falseLabel }] : []),
    ];

    const selectedValue = value === true ? 'true' : hasFalseOption && value === false ? 'false' : '';

    return (
        <AdaptiveSelect
            aria-label={ariaLabel}
            placeholder={allLabel}
            value={selectedValue}
            onValueChange={(selected) => onChange(selected === '' ? null : selected === 'true')}
            options={options}
        />
    );
}

export function FilterBar({ showFilters, children }: React.PropsWithChildren<{ showFilters: boolean }>) {
    return (
        <div
            className={cn(
                'transition-all ease-in-out',
                showFilters
                    ? 'max-h-[1000px] opacity-100 duration-150'
                    : 'pointer-events-none mb-0 max-h-0 overflow-hidden opacity-0 duration-50',
            )}
        >
            <div
                className={cn(
                    'relative z-50 transform transition-transform duration-150',
                    showFilters ? 'translate-y-0' : '-translate-y-4',
                )}
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">{children}</div>
            </div>
        </div>
    );
}
