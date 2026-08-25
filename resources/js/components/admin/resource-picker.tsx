import { type UrlMethodPair } from '@inertiajs/core';
import { ChevronsUpDownIcon, CircleIcon } from 'lucide-react';
import {
    Children,
    cloneElement,
    createContext,
    isValidElement,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type ReactElement,
    type ReactNode,
} from 'react';
import { toast } from 'sonner';

import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogContentContainer,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Item, ItemContent, ItemTitle, ItemDescription } from '@/components/ui/item';
import { RemovableTag } from '@/components/ui/removable-tag';
import { ScrollArea } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Skeleton } from '@/components/ui/skeleton';
import { useDebounce } from '@/hooks/use-debounce';
import { httpGet, isAbortError, isHttpError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const ResourcePickerContext = createContext<{ multiple: boolean }>({ multiple: false });

interface ResourcePickerProps {
    open: boolean;
    onOpenChange?: (open: boolean) => void;
    title?: string;
    description?: string;
    searchQuery: string;
    onSearchChange: (value: string) => void;
    loading?: boolean;
    selectionCount?: number;
    onConfirm: () => void;
    showSelectedOnly?: boolean;
    onToggleShowSelectedOnly?: () => void;
    multiple?: boolean;
}

interface ResourcePickerItem {
    leading?: ReactNode;
    title: string;
    subtitle?: string;
    checked: boolean | 'indeterminate';
    onClick: (e: React.MouseEvent<HTMLButtonElement>) => void;
    disabled?: boolean;
    className?: string;
    truncateSubtitle?: boolean;
}

interface ResourcePickerLoadMoreButtonProps {
    loading?: boolean;
    onClick: () => void;
}

function ResourcePicker({
    open,
    onOpenChange,
    title,
    description,
    searchQuery,
    onSearchChange,
    loading = false,
    selectionCount = 0,
    onConfirm,
    showSelectedOnly = false,
    onToggleShowSelectedOnly,
    multiple = false,
    children,
}: React.PropsWithChildren<ResourcePickerProps>) {
    const hasSelection = selectionCount > 0;
    const isSelectionLabelInteractive = hasSelection && !!onToggleShowSelectedOnly;

    return (
        <ResourcePickerContext.Provider value={{ multiple }}>
            <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
                <AdaptiveDialogContent>
                    <AdaptiveDialogHeader>
                        <AdaptiveDialogTitle>{title}</AdaptiveDialogTitle>
                        <AdaptiveDialogDescription>{description}</AdaptiveDialogDescription>
                    </AdaptiveDialogHeader>

                    <AdaptiveDialogContentContainer className={cn('pb-0 md:py-0 md:pb-2', !description && 'pt-0')}>
                        <SearchInput value={searchQuery} onChange={onSearchChange} loading={loading} />
                        <ScrollArea fade className="[--scroll-fade-color:var(--background)]">
                            <div
                                role={multiple ? 'group' : 'radiogroup'}
                                aria-label={title}
                                className="max-h-[40vh] space-y-4 p-1 pe-3.5 md:max-h-[60vh]"
                            >
                                {children}
                            </div>
                        </ScrollArea>
                    </AdaptiveDialogContentContainer>

                    <AdaptiveDialogFooter>
                        <div className="w-full items-center justify-between space-y-4 md:flex md:space-y-0">
                            {isSelectionLabelInteractive ? (
                                <button
                                    type="button"
                                    aria-pressed={showSelectedOnly}
                                    onClick={onToggleShowSelectedOnly}
                                    className="cursor-default rounded-sm text-sm text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <span>{__(':count selected', { count: selectionCount })}</span>
                                    <span className="ms-0.5">{showSelectedOnly && __('(showing selected only)')}</span>
                                </button>
                            ) : (
                                <div className="text-sm text-muted-foreground">
                                    {hasSelection && __(':count selected', { count: selectionCount })}
                                </div>
                            )}
                            <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button type="button" variant="ghost" onClick={() => onOpenChange?.(false)}>
                                        {__('Cancel')}
                                    </Button>
                                </AdaptiveDialogClose>
                                <Button type="button" onClick={onConfirm}>
                                    {__('Confirm')}
                                </Button>
                            </div>
                        </div>
                    </AdaptiveDialogFooter>
                </AdaptiveDialogContent>
            </AdaptiveDialog>
        </ResourcePickerContext.Provider>
    );
}

function ResourcePickerItem({
    leading,
    title,
    subtitle,
    checked,
    onClick,
    disabled = false,
    className,
    truncateSubtitle = true,
}: ResourcePickerItem) {
    const { multiple } = useContext(ResourcePickerContext);

    return (
        <Item
            asChild
            variant="outline"
            className={cn(
                'w-full min-w-0 cursor-default text-start hover:bg-accent/50 focus-visible:border-border disabled:opacity-50',
                checked && 'bg-accent hover:bg-accent',
                className,
            )}
        >
            <button
                type="button"
                role={multiple ? 'checkbox' : 'radio'}
                aria-checked={checked === 'indeterminate' ? 'mixed' : checked}
                disabled={disabled}
                onClick={onClick}
            >
                {multiple ? (
                    <Checkbox
                        checked={checked}
                        disabled={disabled}
                        aria-hidden
                        tabIndex={-1}
                        className="pointer-events-none"
                    />
                ) : (
                    <ResourcePickerRadio checked={checked === true} disabled={disabled} />
                )}
                {leading && leading}
                <ItemContent className="min-w-0">
                    <ItemTitle className="max-w-full">{title}</ItemTitle>
                    {subtitle && (
                        <ItemDescription className={truncateSubtitle ? 'line-clamp-1' : 'line-clamp-none'}>
                            {subtitle}
                        </ItemDescription>
                    )}
                </ItemContent>
            </button>
        </Item>
    );
}

function ResourcePickerRadio({ checked, disabled }: { checked: boolean; disabled?: boolean }) {
    return (
        <span
            data-state={checked ? 'checked' : 'unchecked'}
            aria-hidden
            className={cn(
                'pointer-events-none relative grid size-4 shrink-0 place-content-center rounded-full border border-input shadow-xs',
                checked && 'border-primary',
                disabled && 'opacity-50',
            )}
        >
            {checked && <CircleIcon className="size-2 fill-primary text-primary" />}
        </span>
    );
}

function ResourcePickerLoadMoreButton({
    loading,
    onClick,
    children,
}: React.PropsWithChildren<ResourcePickerLoadMoreButtonProps>) {
    return (
        <div className="mb-4 text-center">
            <Button variant="outline" size="sm" onClick={onClick} disabled={loading}>
                {children}
            </Button>
        </div>
    );
}

function ResourcePickerSkeleton() {
    return (
        <div className="space-y-4 p-4">
            {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="flex items-start gap-3">
                    <Skeleton className="size-12 rounded-md" />
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-3 w-1/4" />
                    </div>
                </div>
            ))}
        </div>
    );
}

function ResourcePickerEmptyState() {
    return <div className="p-8 text-center text-sm text-muted-foreground">{__('No items found.')}</div>;
}

function useApiList<T>(open: boolean, buildUrl: (query: string, page: number) => UrlMethodPair | null | undefined) {
    const [searchQuery, setSearchQuery] = useState('');
    const [items, setItems] = useState<T[]>([]);
    const [loading, setLoading] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const abortControllerRef = useRef<AbortController | null>(null);
    const [prevOpen, setPrevOpen] = useState(open);

    if (open && !prevOpen) {
        setPrevOpen(true);
        setLoading(true);
        setCurrentPage(1);
    } else if (!open && prevOpen) {
        setPrevOpen(false);
        setSearchQuery('');
        setItems([]);
    }

    const fetchItems = useCallback(
        async (query: string = '', page: number = 1, append: boolean = false) => {
            abortControllerRef.current?.abort();
            abortControllerRef.current = new AbortController();
            setLoading(true);

            try {
                const url = buildUrl(query, page);
                if (!url) {
                    setLoading(false);
                    return;
                }

                const data = await httpGet<Paginated<T>>(url, {
                    signal: abortControllerRef.current.signal,
                });

                setItems((prev) => (append ? [...prev, ...data.data] : data.data));
                setHasMore(data.next_page_url !== null);
                setLoading(false);
            } catch (error) {
                if (isAbortError(error)) {
                    return;
                }
                if (isHttpError(error) && error.data?.message) {
                    toast.error(error.data.message as string);
                } else {
                    toast.error(__('Something went wrong.'));
                }
                setLoading(false);
            }
        },
        [buildUrl],
    );

    const debouncedFetchItems = useDebounce(fetchItems, 300);

    useEffect(() => {
        if (open) {
            debouncedFetchItems(searchQuery);
        }

        return () => abortControllerRef.current?.abort();
    }, [open, searchQuery, debouncedFetchItems]);

    const handleLoadMore = () => {
        if (hasMore && !loading) {
            const nextPage = currentPage + 1;
            setCurrentPage(nextPage);
            setLoading(true);
            fetchItems(searchQuery, nextPage, true);
        }
    };

    const handleSearch = (query: string) => {
        setCurrentPage(1);
        setSearchQuery(query);
    };

    return { items, loading, hasMore, handleLoadMore, searchQuery, setSearchQuery: handleSearch };
}

function useLocalList<T>(
    open: boolean,
    allItems: T[],
    filterItem: (item: T, query: string) => boolean,
    pageSize: number = 15,
) {
    const [searchQuery, setSearchQuery] = useState('');
    const [visibleCount, setVisibleCount] = useState(pageSize);
    const [prevOpen, setPrevOpen] = useState(open);

    if (open !== prevOpen) {
        setPrevOpen(open);
        if (open) {
            setVisibleCount(pageSize);
        } else {
            setSearchQuery('');
        }
    }

    const filteredItems = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();
        if (!query) return allItems;
        return allItems.filter((item) => filterItem(item, query));
    }, [searchQuery, allItems, filterItem]);

    const items = useMemo(() => filteredItems.slice(0, visibleCount), [filteredItems, visibleCount]);
    const hasMore = visibleCount < filteredItems.length;

    const handleLoadMore = () => {
        if (hasMore) setVisibleCount((prevPageSize) => prevPageSize + pageSize);
    };

    const handleSearch = (query: string) => {
        setVisibleCount(pageSize);
        setSearchQuery(query);
    };

    return { items, hasMore, handleLoadMore, searchQuery, setSearchQuery: handleSearch };
}

interface ResourcePickerTriggerProps {
    id?: string;
    name?: string;
    value?: string | number | null;
    label?: string | null;
    placeholder: string;
    onOpen: () => void;
    onRemove?: () => void;
    className?: string;
}

function ResourcePickerTrigger({
    id,
    name,
    value,
    label,
    placeholder,
    onOpen,
    onRemove,
    className,
}: ResourcePickerTriggerProps) {
    const hasValue = Boolean(label);
    const showRemove = Boolean(onRemove) && hasValue;

    return (
        <div className={cn('flex w-full min-w-0', className)}>
            {name && <ReactiveHiddenInput name={name} value={value ?? ''} />}

            <Button
                id={id}
                type="button"
                variant="outline"
                size="md"
                onClick={onOpen}
                title={hasValue ? (label ?? undefined) : undefined}
                className={cn(
                    'min-w-0 flex-1 shrink justify-between overflow-hidden border-input bg-transparent px-3 font-normal shadow-none hover:bg-transparent hover:text-foreground dark:bg-transparent dark:hover:bg-transparent',
                    showRemove && 'rounded-e-none',
                )}
            >
                <span className={cn('min-w-0 truncate text-sm', !hasValue && 'text-muted-foreground')}>
                    {label || placeholder}
                </span>
                <ChevronsUpDownIcon className="shrink-0 text-muted-foreground/50" />
            </Button>

            {showRemove && (
                <Button
                    type="button"
                    variant="outline"
                    size="md"
                    className="rounded-s-none border-s-0"
                    onClick={onRemove}
                >
                    {__('Remove')}
                </Button>
            )}
        </div>
    );
}

function ResourcePickerField({ children }: React.PropsWithChildren) {
    return <div className="space-y-2">{children}</div>;
}

interface ResourcePickerFieldBrowseProps {
    onBrowse: () => void;
}

function ResourcePickerFieldBrowse({ onBrowse, children }: React.PropsWithChildren<ResourcePickerFieldBrowseProps>) {
    return (
        <Button type="button" variant="outline" onClick={onBrowse}>
            {children}
        </Button>
    );
}

interface ResourcePickerFieldTagProps {
    name: string;
    value: string | number;
    onRemove: () => void;
    visible?: boolean;
}

function ResourcePickerFieldTag({
    name,
    value,
    onRemove,
    visible = true,
    children,
}: React.PropsWithChildren<ResourcePickerFieldTagProps>) {
    return (
        <>
            <ReactiveHiddenInput name={name} value={value} />
            {visible && <RemovableTag label={children as string} onRemove={onRemove} />}
        </>
    );
}

interface ResourcePickerFieldTagsProps {
    overflowAfter?: number;
}

function ResourcePickerFieldTags({
    overflowAfter = 3,
    children,
}: React.PropsWithChildren<ResourcePickerFieldTagsProps>) {
    const tags = Children.toArray(children).filter(isValidElement) as ReactElement<ResourcePickerFieldTagProps>[];

    if (tags.length === 0) return null;

    const overflow = tags.length - overflowAfter;

    return (
        <div className="flex flex-wrap gap-2">
            {tags.map((tag, index) => cloneElement(tag, { visible: index < overflowAfter }))}
            {overflow > 0 && (
                <span dir="ltr" className="self-center text-sm text-muted-foreground">
                    +{overflow}
                </span>
            )}
        </div>
    );
}

ResourcePicker.Item = ResourcePickerItem;
ResourcePicker.LoadMoreButton = ResourcePickerLoadMoreButton;
ResourcePicker.Skeleton = ResourcePickerSkeleton;
ResourcePicker.EmptyState = ResourcePickerEmptyState;

ResourcePickerField.Browse = ResourcePickerFieldBrowse;
ResourcePickerField.Tags = ResourcePickerFieldTags;
ResourcePickerField.Tag = ResourcePickerFieldTag;

export { ResourcePicker, ResourcePickerField, ResourcePickerTrigger, useApiList, useLocalList };
