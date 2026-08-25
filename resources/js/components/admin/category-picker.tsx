import { ListTreeIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import CategorySearchController from '@/actions/App/Http/Controllers/Admin/CategorySearchController';
import { ResourcePicker, useApiList } from '@/components/admin/resource-picker';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Category } from '@/types';

export type SelectableItem = Pick<Category, 'id' | 'name'>;

interface CategoryPickerProps<T extends boolean = false> {
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

export function CategoryPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    multiple = false as T,
}: CategoryPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableItem[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const [prevOpen, setPrevOpen] = useState(open);
    const {
        items: categories,
        loading,
        hasMore,
        handleLoadMore,
        searchQuery,
        setSearchQuery,
    } = useApiList<SelectableItem>(open, (query, page) => {
        if (showSelectedOnly) return;
        return CategorySearchController({
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
            ? selection.filter((category) =>
                  getTranslation(category.name).toLowerCase().includes(searchQuery.toLowerCase()),
              )
            : selection;
    }, [showSelectedOnly, searchQuery, selection]);

    const isCategorySelected = (categoryId: number) => {
        return selection.some((item) => item.id === categoryId);
    };

    const { getRange } = useShiftSelect();

    const handleToggleCategory = (category: SelectableItem, e: React.MouseEvent) => {
        const items = showSelectedOnly ? filteredSelectedItems : categories;
        const currentIndex = items.findIndex((item) => item.id === category.id);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = items.slice(range[0], range[1] + 1).filter((item) => !isCategorySelected(item.id));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isCategorySelected(category.id);
            if (isSelected) {
                setSelection(selection.filter((item) => item.id !== category.id));
            } else {
                setSelection(multiple ? [...selection, category] : [category]);
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
            title={title ?? (multiple ? __('Select categories') : __('Select a category'))}
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
                    filteredSelectedItems.map((category) => (
                        <ResourcePicker.Item
                            key={category.id}
                            leading={<CategoryIcon />}
                            title={getTranslation(category.name)}
                            checked={true}
                            onClick={(e) => handleToggleCategory(category, e)}
                        />
                    ))
                )
            ) : loading && categories.length === 0 ? (
                <ResourcePicker.Skeleton />
            ) : categories.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <>
                    {categories.map((category) => (
                        <ResourcePicker.Item
                            key={category.id}
                            leading={<CategoryIcon />}
                            title={getTranslation(category.name)}
                            checked={isCategorySelected(category.id)}
                            onClick={(e) => handleToggleCategory(category, e)}
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

function CategoryIcon() {
    return (
        <div className="relative aspect-square size-10 rounded-md border bg-muted">
            <div className="inline-flex h-full w-full items-center justify-center text-muted-foreground/60">
                <ListTreeIcon className="size-4 rtl:-scale-x-100" />
            </div>
        </div>
    );
}
