import { PlusIcon } from 'lucide-react';
import React, { useState } from 'react';

import { ActionBar } from '@/components/admin/action-bar';
import { ShippingRateDialog } from '@/components/admin/shipping/shipping-rate-dialog';
import { ShippingRateRow } from '@/components/admin/shipping/shipping-rate-row';
import { TablePagination } from '@/components/admin/table-pagination';
import { Button } from '@/components/ui/button';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Paginated, Region, ShippingCarrier, ShippingRate } from '@/types';

interface ShippingRateListProps {
    shippingCarriers: ShippingCarrier[];
    shippingRates: Paginated<ShippingRate>;
    searchQuery: string;
    onSearchQueryChange: (query: string) => void;
    onSort: (field: string) => void;
    getSortIcon: (field: string) => React.ReactElement | null;
    onPageChange: (page: number) => void;
    loading: boolean;
    searchLoading: boolean;
}

export function ShippingRateList({
    shippingCarriers,
    shippingRates,
    searchQuery,
    onSearchQueryChange,
    onSort,
    getSortIcon,
    onPageChange,
    loading,
    searchLoading,
}: ShippingRateListProps) {
    const [selectedRegion, setSelectedRegion] = useState<Region | undefined>(undefined);
    const [selectedShippingRate, setSelectedShippingRate] = useState<ShippingRate | undefined>(undefined);
    const [dialogOpen, setDialogOpen] = useState(false);

    const handleAdd = (region?: Region) => {
        setSelectedRegion(region);
        setSelectedShippingRate(undefined);
        setDialogOpen(true);
    };

    const handleEdit = (shippingRate: ShippingRate) => {
        setSelectedRegion(shippingRate.region);
        setSelectedShippingRate(shippingRate);
        setDialogOpen(true);
    };

    const getCarrierName = (shippingRate: ShippingRate): string => {
        const carrier =
            shippingRate.carrier ??
            shippingCarriers.find((shippingCarrier) => shippingCarrier.id === shippingRate.shipping_carrier_id);

        return getTranslation(carrier?.name);
    };

    return (
        <>
            <ActionBar className="items-end">
                <SearchInput
                    value={searchQuery}
                    onChange={onSearchQueryChange}
                    loading={searchLoading}
                    className="w-xs"
                />

                <Button type="button" variant="outline" onClick={() => handleAdd()}>
                    <PlusIcon className="-ms-0.5 size-3.5" />
                    {__('Add shipping rate')}
                </Button>
            </ActionBar>

            <ScrollArea className="rounded-xl border shadow-xs">
                <Table loading={loading}>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="group cursor-pointer" onClick={() => onSort('name')}>
                                <div className="flex items-center gap-2">
                                    {__('Shipping name')} {getSortIcon('name')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer text-end" onClick={() => onSort('rate')}>
                                <span className="inline-flex items-center">
                                    {__('Shipping rate')}
                                    <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                        {getSortIcon('rate')}
                                    </span>
                                </span>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => onSort('region')}>
                                <div className="flex items-center gap-2">
                                    {__('Region')} {getSortIcon('region')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => onSort('carrier')}>
                                <div className="flex items-center gap-2">
                                    {__('Shipping carrier')} {getSortIcon('carrier')}
                                </div>
                            </TableHead>
                            <TableHead className="group cursor-pointer" onClick={() => onSort('is_active')}>
                                <div className="flex items-center gap-2">
                                    {__('Status')} {getSortIcon('is_active')}
                                </div>
                            </TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {shippingRates.data.map((shippingRate) => (
                            <ShippingRateRow
                                key={shippingRate.id}
                                shippingRate={shippingRate}
                                carrierName={getCarrierName(shippingRate)}
                                onEdit={handleEdit}
                            />
                        ))}
                    </TableBody>

                    <TableFooter>
                        <TableRow>
                            {shippingRates.data.length === 0 ? (
                                <TableCell className="py-8 text-center text-muted-foreground" colSpan={6}>
                                    {__('No items found.')}
                                </TableCell>
                            ) : (
                                <TableCell colSpan={6}>
                                    <TablePagination
                                        from={shippingRates.from}
                                        to={shippingRates.to}
                                        total={shippingRates.total}
                                        currentPage={shippingRates.current_page}
                                        lastPage={shippingRates.last_page}
                                        onPageChange={onPageChange}
                                    />
                                </TableCell>
                            )}
                        </TableRow>
                    </TableFooter>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>

            <ShippingRateDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                shippingCarriers={shippingCarriers}
                region={selectedRegion}
                shippingRate={selectedShippingRate}
            />
        </>
    );
}
