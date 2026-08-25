import { Head, router } from '@inertiajs/react';
import { StarIcon } from 'lucide-react';
import { useState } from 'react';

import * as BulkReviewController from '@/actions/App/Http/Controllers/Admin/BulkReviewController';
import * as ReviewApproveController from '@/actions/App/Http/Controllers/Admin/ReviewApproveController';
import * as ReviewController from '@/actions/App/Http/Controllers/Admin/ReviewController';
import * as ReviewRejectController from '@/actions/App/Http/Controllers/Admin/ReviewRejectController';
import { ActionBar } from '@/components/admin/action-bar';
import { useConfirm } from '@/components/admin/confirm';
import { FilterBar, FilterButton } from '@/components/admin/filter';
import { Heading } from '@/components/admin/heading';
import { ProductPicker, type SelectableItem } from '@/components/admin/product-picker';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { ReviewDialog } from '@/components/admin/review/review-dialog';
import { ReviewRow } from '@/components/admin/review/review-row';
import { SelectedItemActions } from '@/components/admin/selected-item-actions';
import { TablePagination } from '@/components/admin/table-pagination';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useHotkey } from '@/hooks/admin/use-hotkey';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { useOsDetector } from '@/hooks/admin/use-os-detector';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __, transChoice } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Paginated, Review } from '@/types';

interface ReviewListProps {
    reviews: Paginated<Review>;
    filters: {
        query: string | null;
        product: string | null;
        status: string | null;
        rating: string | null;
        sort: string;
        direction: string;
    };
}

export default function ReviewList({ reviews, filters }: ReviewListProps) {
    const [productPickerOpen, setProductPickerOpen] = useState(false);
    const [filterProduct, setFilterProduct] = useState<SelectableItem | null>(null);
    const [reviewDialogOpen, setReviewDialogOpen] = useState(false);
    const [selectedReview, setSelectedReview] = useState<Review | undefined>(undefined);
    const isMacOs = useOsDetector() === 'macOS';
    const { confirm } = useConfirm();
    const { hasPermission } = usePermissions();
    const canEditReview = hasPermission(Permission.ReviewsManage);

    const {
        selectedItems,
        checkedState,
        showFilters,
        setShowFilters,
        loading,
        searchLoading,
        searchQuery,
        searchInputRef,
        hasActiveFilters,
        handleSelectAll,
        handleSelectItem,
        getSortIcon,
        handleSort,
        handleSearchChange,
        handleFilterChange,
        handleResetFilters,
        changePage,
    } = useListManagement({
        data: reviews.data,
        filters,
        fetchOnly: ['reviews'],
    });

    const isEmpty = reviews.data.length === 0 && !hasActiveFilters;

    const handleBulkApprove = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'default',
            title: __('Approve reviews?'),
            description: transChoice(
                'This will approve :count review.|This will approve :count reviews.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        ReviewApproveController.store(),
                        { ids: selectedItems },
                        {
                            preserveScroll: true,
                            only: ['reviews'],
                            onFinish: () => resolve(),
                        },
                    );
                }),
        });
    };

    const handleBulkReject = () => {
        if (selectedItems.length === 0) return;

        confirm({
            title: __('Reject reviews?'),
            description: transChoice(
                'This will reject :count review.|This will reject :count reviews.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        ReviewRejectController.store(),
                        { ids: selectedItems },
                        {
                            preserveScroll: true,
                            only: ['reviews'],
                            onFinish: () => resolve(),
                        },
                    );
                }),
        });
    };

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count review.|This will permanently delete :count reviews.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkReviewController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['reviews'],
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    const resetFilters = () => {
        setFilterProduct(null);
        handleResetFilters();
    };

    const changeProduct = (product: SelectableItem | null) => {
        setFilterProduct(product);
        handleFilterChange('product', product?.id != null ? String(product.id) : '');
    };

    const handleAddReview = () => {
        setSelectedReview(undefined);
        setReviewDialogOpen(true);
    };

    const handleEditReview = (review: Review) => {
        setSelectedReview(review);
        setReviewDialogOpen(true);
    };

    useHotkey(isMacOs ? 'cmd+Backspace' : 'ctrl+Backspace', () => {
        if (selectedItems.length > 0) {
            handleBulkDelete();
        }
    });

    return (
        <>
            <Head title={__('Reviews')} />

            <Heading title={__('Reviews')} description={__('Manage customer reviews and ratings')}>
                {!isEmpty && (
                    <Can permission={Permission.ReviewsManage}>
                        <Button onClick={handleAddReview}>{__('Add review')}</Button>
                    </Can>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <StarIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No reviews')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first review.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.ReviewsManage}>
                        <EmptyContent>
                            <Button onClick={handleAddReview}>{__('Add review')}</Button>
                        </EmptyContent>
                    </Can>
                </Empty>
            ) : (
                <>
                    <ActionBar>
                        <FilterButton
                            showFilters={showFilters}
                            setShowFilters={setShowFilters}
                            filters={filters}
                            onResetFilters={resetFilters}
                        />

                        <Can permissions={[Permission.ReviewsManage, Permission.ReviewsDelete]}>
                            <SelectedItemActions selectedItems={selectedItems}>
                                <Can permission={Permission.ReviewsManage}>
                                    <Button variant="outline" onClick={handleBulkApprove}>
                                        {__('Approve')}
                                    </Button>
                                    <Button variant="outline" onClick={handleBulkReject}>
                                        {__('Reject')}
                                    </Button>
                                </Can>
                                <Can permission={Permission.ReviewsDelete}>
                                    <Button variant="destructive" onClick={handleBulkDelete}>
                                        {__('Delete')}
                                    </Button>
                                </Can>
                            </SelectedItemActions>
                        </Can>
                    </ActionBar>

                    <FilterBar showFilters={showFilters}>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                        />

                        <Can permission={Permission.ProductsReference}>
                            <ResourcePickerTrigger
                                className="min-w-0 flex-1"
                                label={getTranslation(filterProduct?.title)}
                                placeholder={__('All products')}
                                onOpen={() => setProductPickerOpen(true)}
                            />

                            <ProductPicker
                                open={productPickerOpen}
                                onOpenChange={setProductPickerOpen}
                                selectedItems={filterProduct ? [filterProduct] : []}
                                onSelectionChange={changeProduct}
                            />
                        </Can>

                        <AdaptiveSelect
                            placeholder={__('All statuses')}
                            value={filters.status ?? ''}
                            onValueChange={(value) => handleFilterChange('status', value === '' ? null : value)}
                            options={[
                                { value: 'pending', label: __('Pending') },
                                { value: 'approved', label: __('Approved') },
                                { value: 'rejected', label: __('Rejected') },
                            ]}
                        />

                        <AdaptiveSelect
                            placeholder={__('All ratings')}
                            value={filters.rating ?? ''}
                            onValueChange={(value) => handleFilterChange('rating', value === '' ? null : value)}
                            options={[
                                { value: '5', label: __('5 stars') },
                                { value: '4', label: __('4 stars') },
                                { value: '3', label: __('3 stars') },
                                { value: '2', label: __('2 stars') },
                                { value: '1', label: __('1 star') },
                            ]}
                        />
                    </FilterBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <Can permissions={[Permission.ReviewsManage, Permission.ReviewsDelete]}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('rating')}>
                                        <div className="flex items-center gap-2">
                                            {__('Rating')} {getSortIcon('rating')}
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('Review')}</TableHead>
                                    <TableHead>{__('Product')}</TableHead>
                                    <TableHead>{__('Customer')}</TableHead>
                                    <TableHead>{__('Status')}</TableHead>
                                    <TableHead
                                        className="group cursor-pointer"
                                        onClick={() => handleSort('created_at')}
                                    >
                                        <div className="flex items-center gap-2">
                                            {__('Date')} {getSortIcon('created_at')}
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <ThumbnailRatio media={reviews.data.map((review) => review.product?.featured_media)}>
                                    {reviews.data.map((review) => (
                                        <ReviewRow
                                            key={review.id}
                                            review={review}
                                            isSelected={selectedItems.includes(review.id)}
                                            onSelectReview={handleSelectItem}
                                            onEdit={canEditReview ? handleEditReview : undefined}
                                        />
                                    ))}
                                </ThumbnailRatio>
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {reviews.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={7}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={7}>
                                            <TablePagination
                                                from={reviews.from}
                                                to={reviews.to}
                                                total={reviews.total}
                                                currentPage={reviews.current_page}
                                                lastPage={reviews.last_page}
                                                onPageChange={changePage}
                                            />
                                        </TableCell>
                                    )}
                                </TableRow>
                            </TableFooter>
                        </Table>

                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </>
            )}

            <Can permission={Permission.ReviewsManage}>
                <ReviewDialog open={reviewDialogOpen} onOpenChange={setReviewDialogOpen} review={selectedReview} />
            </Can>
        </>
    );
}

ReviewList.layout = {
    breadcrumbs: [{ title: __('Reviews'), href: ReviewController.index() }],
};
