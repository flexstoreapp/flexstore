import { Head, router } from '@inertiajs/react';
import { MapIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import * as BulkRegionController from '@/actions/App/Http/Controllers/Admin/BulkRegionController';
import * as RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { ActionBar } from '@/components/admin/action-bar';
import { useConfirm } from '@/components/admin/confirm';
import { Heading } from '@/components/admin/heading';
import { RegionDialog } from '@/components/admin/region/region-dialog';
import { RegionRow } from '@/components/admin/region/region-row';
import { SelectedItemActions } from '@/components/admin/selected-item-actions';
import { TablePagination } from '@/components/admin/table-pagination';
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
import { __, transChoice } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Paginated, Region } from '@/types';

interface RegionListProps {
    regions: Paginated<Region>;
    filters: {
        query: string | null;
        sort: string;
        direction: string;
        page: number;
    };
}

export default function RegionList({ regions, filters }: RegionListProps) {
    const [regionDialogOpen, setRegionDialogOpen] = useState(false);
    const [selectedRegion, setSelectedRegion] = useState<Region | undefined>(undefined);
    const isMacOs = useOsDetector() === 'macOS';
    const { confirm } = useConfirm();

    const {
        selectedItems,
        checkedState,
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
        changePage,
    } = useListManagement({
        data: regions.data,
        filters,
        fetchOnly: ['regions'],
    });

    const isEmpty = regions.data.length === 0 && !hasActiveFilters;

    const handleAddRegion = () => {
        setSelectedRegion(undefined);
        setRegionDialogOpen(true);
    };

    const handleEditRegion = (region: Region) => {
        setSelectedRegion(region);
        setRegionDialogOpen(true);
    };

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count region.|This will permanently delete :count regions.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkRegionController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['regions'],
                        onError: (errors) => toast.error(errors.ids),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    useHotkey(isMacOs ? 'cmd+Backspace' : 'ctrl+Backspace', () => {
        if (selectedItems.length > 0) {
            handleBulkDelete();
        }
    });

    return (
        <>
            <Head title={__('Regions')} />

            <Heading title={__('Regions')} description={__('Manage regions for shipping and taxes')}>
                {!isEmpty && (
                    <Can permission={Permission.RegionsManage}>
                        <Button onClick={handleAddRegion}>{__('Add region')}</Button>
                    </Can>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <MapIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No regions')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first region.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.RegionsManage}>
                        <EmptyContent>
                            <Button onClick={handleAddRegion}>{__('Add region')}</Button>
                        </EmptyContent>
                    </Can>
                </Empty>
            ) : (
                <>
                    <ActionBar>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                            className="w-xs"
                        />

                        <Can permissions={[Permission.RegionsManage, Permission.RegionsDelete]}>
                            <SelectedItemActions selectedItems={selectedItems}>
                                <Can permission={Permission.RegionsDelete}>
                                    <Button variant="destructive" onClick={handleBulkDelete}>
                                        {__('Delete')}
                                    </Button>
                                </Can>
                            </SelectedItemActions>
                        </Can>
                    </ActionBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <Can permissions={[Permission.RegionsManage, Permission.RegionsDelete]}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('name')}>
                                        <div className="flex items-center gap-2">
                                            {__('Region')} {getSortIcon('name')}
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('Countries')}</TableHead>
                                    <TableHead>{__('States')}</TableHead>
                                    <TableHead>{__('Postal codes')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {regions.data.map((region) => (
                                    <RegionRow
                                        key={region.id}
                                        region={region}
                                        isSelected={selectedItems.includes(region.id)}
                                        onSelectRegion={handleSelectItem}
                                        onEdit={handleEditRegion}
                                    />
                                ))}
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {regions.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={5}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={5}>
                                            <TablePagination
                                                from={regions.from}
                                                to={regions.to}
                                                total={regions.total}
                                                currentPage={regions.current_page}
                                                lastPage={regions.last_page}
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

            <Can permission={Permission.RegionsManage}>
                <RegionDialog open={regionDialogOpen} onOpenChange={setRegionDialogOpen} region={selectedRegion} />
            </Can>
        </>
    );
}

RegionList.layout = {
    breadcrumbs: [{ title: __('Regions'), href: RegionController.index() }],
};
