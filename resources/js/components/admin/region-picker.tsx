import { MapPinIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import RegionSearchController from '@/actions/App/Http/Controllers/Admin/RegionSearchController';
import { ResourcePicker, useApiList } from '@/components/admin/resource-picker';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Region } from '@/types';

export type SelectableItem = Pick<Region, 'id' | 'name'>;

interface RegionPickerProps<T extends boolean = false> {
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

export function RegionPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    multiple = false as T,
}: RegionPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableItem[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const [prevOpen, setPrevOpen] = useState(open);
    const {
        items: regions,
        loading,
        hasMore,
        handleLoadMore,
        searchQuery,
        setSearchQuery,
    } = useApiList<SelectableItem>(open, (query, page) => {
        if (showSelectedOnly) return;
        return RegionSearchController({
            mergeQuery: { query, page },
        });
    });

    if (open && !prevOpen) {
        setPrevOpen(true);
        setSelection(selectedItems);
        setShowSelectedOnly(false);
    } else if (!open && prevOpen) {
        setPrevOpen(false);
    }

    const filteredSelectedItems = useMemo(() => {
        if (!showSelectedOnly) return [];

        return searchQuery
            ? selection.filter((region) =>
                  getTranslation(region.name).toLowerCase().includes(searchQuery.toLowerCase()),
              )
            : selection;
    }, [selection, searchQuery, showSelectedOnly]);

    const isRegionSelected = (regionId: number) => {
        return selection.some((item) => item.id === regionId);
    };

    const { getRange } = useShiftSelect();

    const handleToggleRegion = (region: SelectableItem, e: React.MouseEvent) => {
        const items = showSelectedOnly ? filteredSelectedItems : regions;
        const currentIndex = items.findIndex((item) => item.id === region.id);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = items.slice(range[0], range[1] + 1).filter((item) => !isRegionSelected(item.id));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isRegionSelected(region.id);
            if (isSelected) {
                setSelection(selection.filter((item) => item.id !== region.id));
            } else {
                setSelection(multiple ? [...selection, region] : [region]);
            }
        }
    };

    const handleConfirm = () => {
        if (multiple) {
            (onSelectionChange as (selection: SelectableItem[]) => void)?.(selection);
        } else {
            (onSelectionChange as (selection: SelectableItem | null) => void)?.(selection[0] ?? null);
        }

        onOpenChange?.(false);
    };

    return (
        <ResourcePicker
            open={open}
            onOpenChange={onOpenChange}
            title={title ?? (multiple ? __('Select regions') : __('Select a region'))}
            description={description}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            loading={loading}
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
                    filteredSelectedItems.map((region) => (
                        <ResourcePicker.Item
                            key={region.id}
                            leading={<RegionIcon />}
                            title={getTranslation(region.name)}
                            checked={true}
                            onClick={(e) => handleToggleRegion(region, e)}
                        />
                    ))
                )
            ) : loading && regions.length === 0 ? (
                <ResourcePicker.Skeleton />
            ) : regions.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <>
                    {regions.map((region) => (
                        <ResourcePicker.Item
                            key={region.id}
                            leading={<RegionIcon />}
                            title={getTranslation(region.name)}
                            checked={isRegionSelected(region.id)}
                            onClick={(e) => handleToggleRegion(region, e)}
                        />
                    ))}

                    {hasMore && (
                        <ResourcePicker.LoadMoreButton loading={loading} onClick={handleLoadMore}>
                            {loading ? __('Loading...') : __('Load more')}
                        </ResourcePicker.LoadMoreButton>
                    )}
                </>
            )}
        </ResourcePicker>
    );
}

function RegionIcon() {
    return (
        <div className="relative aspect-square size-10 rounded-md border bg-muted">
            <div className="inline-flex h-full w-full items-center justify-center text-muted-foreground/60">
                <MapPinIcon className="size-4" />
            </div>
        </div>
    );
}
