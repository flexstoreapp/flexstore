import { router } from '@inertiajs/react';
import { MapPinIcon, PlusIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import * as CustomerAddressController from '@/actions/App/Http/Controllers/Admin/CustomerAddressController';
import SetDefaultCustomerAddressController from '@/actions/App/Http/Controllers/Admin/SetDefaultCustomerAddressController';
import { useConfirm } from '@/components/admin/confirm';
import { AddressCard } from '@/components/admin/customer/address-card';
import { AddressDialog } from '@/components/admin/customer/address-dialog';
import { SectionHeading } from '@/components/admin/section-heading';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { __ } from '@/lib/i18n';
import type { CustomerAddress } from '@/types';

interface CustomerAddressListProps {
    addresses: CustomerAddress[];
    customerId: number;
}

export function CustomerAddressList({ addresses, customerId }: CustomerAddressListProps) {
    const [selectedAddress, setSelectedAddress] = useState<CustomerAddress | undefined>(undefined);
    const [dialogOpen, setDialogOpen] = useState(false);
    const { confirm } = useConfirm();

    const handleAdd = () => {
        setSelectedAddress(undefined);
        setDialogOpen(true);
    };

    const handleEdit = (address: CustomerAddress) => {
        setSelectedAddress(address);
        setDialogOpen(true);
    };

    const handleSetDefault = (address: CustomerAddress) => {
        router.post(
            SetDefaultCustomerAddressController({ customer: customerId, address: address.id }),
            {},
            { preserveScroll: true, only: ['addresses', 'customer'] },
        );
    };

    const handleDelete = (address: CustomerAddress) => {
        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: __('This will permanently delete this address.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(CustomerAddressController.destroy({ customer: customerId, address: address.id }), {
                        preserveScroll: true,
                        only: ['addresses', 'customer'],
                        onError: (errors) => toast.error(errors.address),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    return (
        <>
            <section className="space-y-4">
                <div className="flex items-end justify-between gap-2">
                    <SectionHeading>{__('Addresses')}</SectionHeading>
                    {addresses.length > 0 && (
                        <Button variant="outline" onClick={handleAdd}>
                            <PlusIcon className="-ms-0.5 size-3.5" />
                            {__('Add address')}
                        </Button>
                    )}
                </div>

                <div>
                    {addresses.length === 0 ? (
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <MapPinIcon />
                                </EmptyMedia>
                                <EmptyTitle>{__('No addresses')}</EmptyTitle>
                                <EmptyDescription>
                                    {__('This customer hasn’t saved any addresses yet.')}
                                </EmptyDescription>
                            </EmptyHeader>
                            <EmptyContent>
                                <Button variant="outline" onClick={handleAdd}>
                                    <PlusIcon />
                                    {__('Add address')}
                                </Button>
                            </EmptyContent>
                        </Empty>
                    ) : (
                        <div className="space-y-4">
                            <ScrollArea>
                                <div className="grid gap-4">
                                    {addresses.map((address) => (
                                        <AddressCard
                                            key={address.id}
                                            address={address}
                                            onSetDefault={handleSetDefault}
                                            onEdit={handleEdit}
                                            onDelete={handleDelete}
                                        />
                                    ))}
                                </div>

                                <ScrollBar orientation="horizontal" />
                            </ScrollArea>
                        </div>
                    )}
                </div>
            </section>

            <AddressDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                address={selectedAddress}
                formAction={
                    selectedAddress
                        ? CustomerAddressController.update.form({ customer: customerId, address: selectedAddress.id })
                        : CustomerAddressController.store.form(customerId)
                }
                formOptions={{ only: ['addresses', 'customer'] }}
                resetOnSuccess={!selectedAddress}
            />
        </>
    );
}
