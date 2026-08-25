import { UserRoundIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import UserSearchController from '@/actions/App/Http/Controllers/Admin/UserSearchController';
import { ResourcePicker, useApiList } from '@/components/admin/resource-picker';
import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { __ } from '@/lib/i18n';
import type { User } from '@/types';

export type SelectableUser = Pick<User, 'id' | 'name' | 'email'>;

interface UserPickerProps<T extends boolean = false> {
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    selectedItems?: SelectableUser[];
    onSelectionChange?: T extends true
        ? (selection: SelectableUser[]) => void
        : (selection: SelectableUser | null) => void;
    title?: string;
    description?: string;
    customersOnly?: boolean;
    multiple?: T;
}

export function UserPicker<T extends boolean = false>({
    open = false,
    onOpenChange,
    selectedItems = [],
    onSelectionChange,
    title,
    description,
    customersOnly = false,
    multiple = false as T,
}: UserPickerProps<T>) {
    const [selection, setSelection] = useState<SelectableUser[]>(selectedItems);
    const [showSelectedOnly, setShowSelectedOnly] = useState(false);
    const [prevOpen, setPrevOpen] = useState(open);
    const {
        items: users,
        loading,
        hasMore,
        handleLoadMore,
        searchQuery,
        setSearchQuery,
    } = useApiList<SelectableUser>(open, (query, page) => {
        if (showSelectedOnly) return;
        return UserSearchController({
            mergeQuery: { query, page, customers_only: customersOnly ? '1' : '0' },
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
            ? selection.filter(
                  (user) =>
                      user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                      user.email.toLowerCase().includes(searchQuery.toLowerCase()),
              )
            : selection;
    }, [selection, searchQuery, showSelectedOnly]);

    let pickerTitle = title;
    if (!pickerTitle) {
        if (multiple) {
            pickerTitle = customersOnly ? __('Select customers') : __('Select users');
        } else {
            pickerTitle = customersOnly ? __('Select a customer') : __('Select a user');
        }
    }

    const isUserSelected = (userId: number) => {
        return selection.some((item) => item.id === userId);
    };

    const { getRange } = useShiftSelect();

    const handleToggleUser = (user: SelectableUser, e: React.MouseEvent) => {
        const currentItems = showSelectedOnly ? filteredSelectedItems : users;
        const currentIndex = currentItems.findIndex((item) => item.id === user.id);
        const range = multiple ? getRange(currentIndex, e.shiftKey) : null;

        if (range) {
            const rangeItems = currentItems.slice(range[0], range[1] + 1).filter((item) => !isUserSelected(item.id));
            setSelection((prev) => [...prev, ...rangeItems]);
        } else {
            const isSelected = isUserSelected(user.id);
            if (isSelected) {
                setSelection(selection.filter((item) => item.id !== user.id));
            } else {
                setSelection(multiple ? [...selection, user] : [user]);
            }
        }
    };

    const handleConfirm = () => {
        if (multiple) {
            (onSelectionChange as (selection: SelectableUser[]) => void)?.(selection);
        } else {
            (onSelectionChange as (selection: SelectableUser | null) => void)?.(selection[0] ?? null);
        }

        onOpenChange?.(false);
    };

    return (
        <ResourcePicker
            open={open}
            onOpenChange={onOpenChange}
            title={pickerTitle}
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
                    filteredSelectedItems.map((user) => (
                        <ResourcePicker.Item
                            key={user.id}
                            leading={<UserIconComponent />}
                            title={user.name}
                            subtitle={user.email}
                            checked={true}
                            onClick={(e) => handleToggleUser(user, e)}
                        />
                    ))
                )
            ) : loading && users.length === 0 ? (
                <ResourcePicker.Skeleton />
            ) : users.length === 0 ? (
                <ResourcePicker.EmptyState />
            ) : (
                <>
                    {users.map((user) => (
                        <ResourcePicker.Item
                            key={user.id}
                            leading={<UserIconComponent />}
                            title={user.name}
                            subtitle={user.email}
                            checked={isUserSelected(user.id)}
                            onClick={(e) => handleToggleUser(user, e)}
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

function UserIconComponent() {
    return (
        <div className="relative aspect-square size-10 rounded-md border bg-muted">
            <div className="inline-flex h-full w-full items-center justify-center text-muted-foreground/60">
                <UserRoundIcon className="size-4" />
            </div>
        </div>
    );
}
