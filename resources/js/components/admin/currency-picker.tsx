import { CircleDollarSignIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import { ResourcePicker, useLocalList } from '@/components/admin/resource-picker';
import { useCurrencies } from '@/hooks/admin/use-currencies';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { __ } from '@/lib/i18n';

export type SelectableItem = { code: string; name: string };

interface CurrencyPickerProps<T extends boolean = false> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    selectedItems?: SelectableItem[];
    onSelectionChange?: T extends true
        ? (selection: SelectableItem[]) => void
        : (selection: SelectableItem | null) => void;
    title?: string;
    description?: string;
    multiple?: T;
}

export function CurrencyPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    multiple = false as T,
}: CurrencyPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableItem[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const { currencyOptions } = useCurrencies();

    const allCurrencies: SelectableItem[] = useMemo(() => {
        return currencyOptions.map(({ value, label }) => ({ code: value, name: label }));
    }, [currencyOptions]);

    const filterCurrency = (currency: SelectableItem, query: string) => {
        if (showSelectedOnly) return false;
        return currency.code.toLowerCase().includes(query) || currency.name.toLowerCase().includes(query);
    };

    const { items, hasMore, handleLoadMore, searchQuery, setSearchQuery } = useLocalList<SelectableItem>(
        open,
        allCurrencies,
        filterCurrency,
    );

    const filteredSelectedItems = useMemo(() => {
        if (!showSelectedOnly) return [];

        return searchQuery
            ? selection.filter(
                  (currency) =>
                      currency.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
                      currency.name.toLowerCase().includes(searchQuery.toLowerCase()),
              )
            : selection;
    }, [selection, searchQuery, showSelectedOnly]);

    const [prevOpen, setPrevOpen] = useState(open);
    const selectedItemsKey = selectedItems.map((item) => item.code).join(',');
    const [prevSelectedItemsKey, setPrevSelectedItemsKey] = useState(selectedItemsKey);

    if (open !== prevOpen || selectedItemsKey !== prevSelectedItemsKey) {
        setPrevOpen(open);
        setPrevSelectedItemsKey(selectedItemsKey);
        if (open) {
            const currencyMap = new Map(allCurrencies.map((c) => [c.code, c]));
            setSelection(selectedItems.map((item) => currencyMap.get(item.code) ?? item));
            setShowSelectedOnly(false);
        }
    }

    const isCurrencySelected = (currencyCode: string) => {
        return selection.some((item) => item.code === currencyCode);
    };

    const { getRange } = useShiftSelect();

    const handleToggleCurrency = (currency: SelectableItem, e: React.MouseEvent) => {
        const currentItems = showSelectedOnly ? filteredSelectedItems : items;
        const currentIndex = currentItems.findIndex((item) => item.code === currency.code);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = currentItems
                .slice(range[0], range[1] + 1)
                .filter((item) => !isCurrencySelected(item.code));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isCurrencySelected(currency.code);
            if (isSelected) {
                setSelection((prev) => prev.filter((item) => item.code !== currency.code));
            } else {
                setSelection((prev) => (multiple ? [...prev, currency] : [currency]));
            }
        }
    };

    const handleConfirm = () => {
        if (multiple) {
            (onSelectionChange as (selection: SelectableItem[]) => void)?.(selection);
        } else {
            (onSelectionChange as (selection: SelectableItem | null) => void)?.(selection[0] ?? null);
        }

        handleClose();
    };

    const handleClose = () => {
        setSelection([]);
        onOpenChange?.(false);
    };

    return (
        <ResourcePicker
            open={open}
            onOpenChange={handleClose}
            title={title ?? (multiple ? __('Select currencies') : __('Select a currency'))}
            description={description}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            selectionCount={selection.length}
            onConfirm={handleConfirm}
            showSelectedOnly={showSelectedOnly}
            onToggleShowSelectedOnly={() => setShowSelectedOnly((prev) => !prev)}
            multiple={multiple}
        >
            {showSelectedOnly ? (
                filteredSelectedItems.length === 0 ? (
                    <ResourcePicker.EmptyState />
                ) : (
                    <div className="space-y-4">
                        {filteredSelectedItems.map((currency) => (
                            <ResourcePicker.Item
                                key={currency.code}
                                leading={<CurrencyIcon />}
                                title={currency.name}
                                subtitle={currency.code}
                                checked={true}
                                onClick={(e) => handleToggleCurrency(currency, e)}
                            />
                        ))}
                    </div>
                )
            ) : items.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <div className="space-y-4">
                    {items.map((currency) => (
                        <ResourcePicker.Item
                            key={currency.code}
                            leading={<CurrencyIcon />}
                            title={currency.name}
                            subtitle={currency.code}
                            checked={isCurrencySelected(currency.code)}
                            onClick={(e) => handleToggleCurrency(currency, e)}
                        />
                    ))}

                    {hasMore && (
                        <ResourcePicker.LoadMoreButton onClick={handleLoadMore}>
                            {__('Load more')}
                        </ResourcePicker.LoadMoreButton>
                    )}
                </div>
            )}
        </ResourcePicker>
    );
}

function CurrencyIcon() {
    return (
        <div className="relative aspect-square size-10 rounded-md border bg-muted">
            <div className="inline-flex h-full w-full items-center justify-center text-muted-foreground/60">
                <CircleDollarSignIcon className="size-4" />
            </div>
        </div>
    );
}
