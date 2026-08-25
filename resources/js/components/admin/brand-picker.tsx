import { useMemo, useState } from 'react';

import BrandSearchController from '@/actions/App/Http/Controllers/Admin/BrandSearchController';
import { ResourcePicker, useApiList } from '@/components/admin/resource-picker';
import { Thumbnail } from '@/components/admin/thumbnail';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { __ } from '@/lib/i18n';
import { mediaAlt } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { Brand } from '@/types';

export type SelectableBrand = Pick<Brand, 'id' | 'name' | 'image'>;

interface BrandPickerProps<T extends boolean = false> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    selectedItems?: SelectableBrand[];
    onSelectionChange?: T extends true
        ? (selection: SelectableBrand[]) => void
        : (selection: SelectableBrand | null) => void;
    title?: string;
    description?: string;
    multiple?: T;
}

export function BrandPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    multiple = false as T,
}: BrandPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableBrand[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const [prevOpen, setPrevOpen] = useState(open);
    const {
        items: brands,
        loading,
        hasMore,
        handleLoadMore,
        searchQuery,
        setSearchQuery,
    } = useApiList<SelectableBrand>(open, (query, page) => {
        if (showSelectedOnly) return;
        return BrandSearchController({
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
            ? selection.filter((brand) => getTranslation(brand.name).toLowerCase().includes(searchQuery.toLowerCase()))
            : selection;
    }, [selection, searchQuery, showSelectedOnly]);

    const isBrandSelected = (brandId: number) => {
        return selection.some((item) => item.id === brandId);
    };

    const { getRange } = useShiftSelect();

    const handleToggleBrand = (brand: SelectableBrand, e: React.MouseEvent) => {
        const items = showSelectedOnly ? filteredSelectedItems : brands;
        const currentIndex = items.findIndex((item) => item.id === brand.id);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = items.slice(range[0], range[1] + 1).filter((item) => !isBrandSelected(item.id));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isBrandSelected(brand.id);
            if (isSelected) {
                setSelection(selection.filter((item) => item.id !== brand.id));
            } else {
                setSelection(multiple ? [...selection, brand] : [brand]);
            }
        }
    };

    const handleConfirm = () => {
        if (multiple) {
            (onSelectionChange as (selection: SelectableBrand[]) => void)?.(selection);
        } else {
            (onSelectionChange as (selection: SelectableBrand | null) => void)?.(selection[0] ?? null);
        }

        onOpenChange?.(false);
    };

    return (
        <ResourcePicker
            open={open}
            onOpenChange={onOpenChange}
            title={title ?? (multiple ? __('Select brands') : __('Select a brand'))}
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
                    filteredSelectedItems.map((brand) => (
                        <ResourcePicker.Item
                            key={brand.id}
                            leading={
                                <Thumbnail
                                    src={brand.image?.url ?? null}
                                    alt={mediaAlt(brand.image, getTranslation(brand.name))}
                                />
                            }
                            title={getTranslation(brand.name)}
                            checked={true}
                            onClick={(e) => handleToggleBrand(brand, e)}
                        />
                    ))
                )
            ) : loading && brands.length === 0 ? (
                <ResourcePicker.Skeleton />
            ) : brands.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <>
                    {brands.map((brand) => (
                        <ResourcePicker.Item
                            key={brand.id}
                            leading={
                                <Thumbnail
                                    src={brand.image?.url ?? null}
                                    alt={mediaAlt(brand.image, getTranslation(brand.name))}
                                />
                            }
                            title={getTranslation(brand.name)}
                            checked={isBrandSelected(brand.id)}
                            onClick={(e) => handleToggleBrand(brand, e)}
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
