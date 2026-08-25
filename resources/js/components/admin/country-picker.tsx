import { MapPinIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import { ResourcePicker, useLocalList } from '@/components/admin/resource-picker';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';

export type SelectableItem = { code: string; name: string };

interface CountryPickerProps<T extends boolean = false> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    selectedItems?: SelectableItem[];
    onSelectionChange?: T extends true
        ? (selection: SelectableItem[]) => void
        : (selection: SelectableItem | null) => void;
    title?: string;
    description?: string;
    multiple?: T;
    includeAll?: boolean;
}

export function CountryPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    multiple = false as T,
    includeAll = false,
}: CountryPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableItem[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const { countryOptions } = useCountries({ all: includeAll });

    const allCountries: SelectableItem[] = useMemo(() => {
        return countryOptions.map(({ value, label }) => ({ code: value, name: label }));
    }, [countryOptions]);

    const filterCountry = (country: SelectableItem, query: string) => {
        if (showSelectedOnly) return false;
        return country.code.toLowerCase().includes(query) || country.name.toLowerCase().includes(query);
    };

    const { items, hasMore, handleLoadMore, searchQuery, setSearchQuery } = useLocalList<SelectableItem>(
        open,
        allCountries,
        filterCountry,
    );

    const filteredSelectedItems = useMemo(() => {
        if (!showSelectedOnly) return [];

        return searchQuery
            ? selection.filter(
                  (country) =>
                      country.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
                      country.name.toLowerCase().includes(searchQuery.toLowerCase()),
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
            setSelection(selectedItems);
            setShowSelectedOnly(false);
        }
    }

    const isCountrySelected = (countryCode: string) => {
        return selection.some((item) => item.code === countryCode);
    };

    const { getRange } = useShiftSelect();

    const handleToggleCountry = (country: SelectableItem, e: React.MouseEvent) => {
        const currentItems = showSelectedOnly ? filteredSelectedItems : items;
        const currentIndex = currentItems.findIndex((item) => item.code === country.code);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = currentItems
                .slice(range[0], range[1] + 1)
                .filter((item) => !isCountrySelected(item.code));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isCountrySelected(country.code);
            if (isSelected) {
                setSelection(selection.filter((item) => item.code !== country.code));
            } else {
                setSelection(multiple ? [...selection, country] : [country]);
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
            title={title ?? (multiple ? __('Select countries') : __('Select a country'))}
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
                        {filteredSelectedItems.map((country) => (
                            <ResourcePicker.Item
                                key={country.code}
                                leading={<CountryIcon />}
                                title={country.name}
                                subtitle={country.code}
                                checked={true}
                                onClick={(e) => handleToggleCountry(country, e)}
                            />
                        ))}
                    </div>
                )
            ) : items.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <div className="space-y-4">
                    {items.map((country) => (
                        <ResourcePicker.Item
                            key={country.code}
                            leading={<CountryIcon />}
                            title={country.name}
                            subtitle={country.code}
                            checked={isCountrySelected(country.code)}
                            onClick={(e) => handleToggleCountry(country, e)}
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

function CountryIcon() {
    return (
        <div className="relative aspect-square size-10 rounded-md border bg-muted">
            <div className="inline-flex h-full w-full items-center justify-center text-muted-foreground/60">
                <MapPinIcon className="size-4" />
            </div>
        </div>
    );
}
