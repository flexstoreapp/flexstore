import { PlusIcon } from 'lucide-react';
import { useState } from 'react';

import { ActionBar } from '@/components/admin/action-bar';
import { TablePagination } from '@/components/admin/table-pagination';
import { TaxRateDialog } from '@/components/admin/tax/tax-rate-dialog';
import { TaxRateRow } from '@/components/admin/tax/tax-rate-row';
import { Button } from '@/components/ui/button';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { __ } from '@/lib/i18n';
import type { Paginated, TaxCategoryOption, TaxRate } from '@/types';

interface TaxRateListProps {
    taxRates: Paginated<TaxRate>;
    taxCategories: TaxCategoryOption[];
    filters: {
        query: string | null;
        page: number;
        sort: string;
        direction: string;
    };
}

export function TaxRateList({ taxRates, taxCategories, filters }: TaxRateListProps) {
    const [selectedTaxRate, setSelectedTaxRate] = useState<TaxRate | undefined>(undefined);
    const [dialogOpen, setDialogOpen] = useState(false);
    const { loading, searchLoading, searchQuery, getSortIcon, handleSort, handleSearchChange, handleFilterChange } =
        useListManagement({
            data: taxRates.data,
            filters,
            fetchOnly: ['taxRates'],
        });

    const handleAdd = () => {
        setSelectedTaxRate(undefined);
        setDialogOpen(true);
    };

    const handleEdit = (taxRate: TaxRate) => {
        setSelectedTaxRate(taxRate);
        setDialogOpen(true);
    };

    return (
        <>
            <ActionBar className="items-end">
                <SearchInput
                    value={searchQuery}
                    onChange={handleSearchChange}
                    loading={searchLoading}
                    className="w-xs"
                />

                <Button variant="outline" onClick={handleAdd}>
                    <PlusIcon className="-ms-0.5 size-3.5" />
                    {__('Add tax rate')}
                </Button>
            </ActionBar>

            <ScrollArea className="rounded-xl border shadow-xs">
                <Table loading={loading}>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="group cursor-pointer" onClick={() => handleSort('name')}>
                                <div className="flex items-center gap-2">
                                    {__('Tax name')} {getSortIcon('name')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer text-end" onClick={() => handleSort('rate')}>
                                <span className="inline-flex items-center">
                                    {__('Tax rate')}
                                    <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                        {getSortIcon('rate')}
                                    </span>
                                </span>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => handleSort('region')}>
                                <div className="flex items-center gap-2">
                                    {__('Region')} {getSortIcon('region')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => handleSort('tax_category')}>
                                <div className="flex items-center gap-2">
                                    {__('Tax category')} {getSortIcon('tax_category')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => handleSort('is_active')}>
                                <div className="flex items-center gap-2">
                                    {__('Status')} {getSortIcon('is_active')}
                                </div>
                            </TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {taxRates.data.map((taxRate) => (
                            <TaxRateRow
                                key={taxRate.id}
                                taxRate={taxRate}
                                taxCategories={taxCategories}
                                onEdit={handleEdit}
                            />
                        ))}
                    </TableBody>

                    <TableFooter>
                        <TableRow>
                            {taxRates.data.length === 0 ? (
                                <TableCell className="py-8 text-center text-muted-foreground" colSpan={6}>
                                    {__('No items found.')}
                                </TableCell>
                            ) : (
                                <TableCell colSpan={6}>
                                    <TablePagination
                                        from={taxRates.from}
                                        to={taxRates.to}
                                        total={taxRates.total}
                                        currentPage={taxRates.current_page}
                                        lastPage={taxRates.last_page}
                                        onPageChange={(page: number) => handleFilterChange('page', page)}
                                    />
                                </TableCell>
                            )}
                        </TableRow>
                    </TableFooter>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>

            <TaxRateDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                taxRate={selectedTaxRate}
                taxCategories={taxCategories}
            />
        </>
    );
}
