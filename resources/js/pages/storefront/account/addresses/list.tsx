import { router } from '@inertiajs/react';
import { MapPinIcon, PlusIcon } from 'lucide-react';
import { useState } from 'react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import SetDefaultAddressController from '@/actions/App/Http/Controllers/Storefront/SetDefaultAddressController';
import { AccountAddressForm } from '@/components/storefront/account/account-address-form';
import { AccountEmptyState } from '@/components/storefront/account/account-empty-state';
import { AddressSummary } from '@/components/storefront/account/address-summary';
import { Button } from '@/components/storefront/button';
import { useConfirm } from '@/components/storefront/confirm';
import { Dialog } from '@/components/storefront/dialog';
import { StatusBadge } from '@/components/storefront/status-badge';
import { __ } from '@/lib/i18n';
import type { CustomerAddress } from '@/types';

export default function Addresses({ addresses }: { addresses: CustomerAddress[] }) {
    const { confirm } = useConfirm();
    const [modal, setModal] = useState<{ open: boolean; address: CustomerAddress | null }>({
        open: false,
        address: null,
    });
    const [deleting, setDeleting] = useState<CustomerAddress | null>(null);
    const [dirty, setDirty] = useState(false);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const closeModal = () => {
        setDirty(false);
        setModal((current) => ({ ...current, open: false }));
    };

    const requestClose = async () => {
        if (
            dirty &&
            !(await confirm({
                title: __('Discard changes?'),
                description: __('You have unsaved changes. Are you sure you want to discard them?'),
                confirmLabel: __('Discard'),
                cancelLabel: __('Keep editing'),
                variant: 'error',
            }))
        ) {
            return;
        }

        closeModal();
    };

    const setDefault = (address: CustomerAddress) => {
        router.post(SetDefaultAddressController(address.id).url, {}, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(AddressController.destroy(deleting.id).url, {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <>
            {addresses.length === 0 ? (
                <div className="rounded-md border border-line bg-surface">
                    <AccountEmptyState
                        icon={<MapPinIcon size={28} strokeWidth={1.6} aria-hidden="true" />}
                        title={__('No saved addresses')}
                        description={__('Add an address for faster checkout.')}
                        action={
                            <Button size="md" onClick={() => setModal({ open: true, address: null })}>
                                {__('Add new address')}
                            </Button>
                        }
                    />
                </div>
            ) : (
                <div className="grid items-start gap-4 md:grid-cols-2">
                    {addresses.map((address) => (
                        <section key={address.id} className="flex flex-col rounded-md border border-line bg-surface">
                            <div className="flex-1 p-5">
                                <AddressSummary address={address} />
                            </div>
                            <div className="flex h-14 items-center gap-3 border-t border-line px-5">
                                {address.is_default ? (
                                    <span className="me-auto">
                                        <StatusBadge tone="info">{__('Default')}</StatusBadge>
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setDefault(address)}
                                        className="me-auto text-sm font-semibold text-primary underline-offset-2 hover:underline"
                                    >
                                        {__('Set as default')}
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setDeleting(address)}
                                    className="text-sm font-semibold text-muted transition-colors can-hover:hover:text-error"
                                >
                                    {__('Delete')}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setModal({ open: true, address })}
                                    className="text-sm font-semibold text-primary underline-offset-2 hover:underline"
                                >
                                    {__('Edit')}
                                </button>
                            </div>
                        </section>
                    ))}

                    <AddButton onClick={() => setModal({ open: true, address: null })} />
                </div>
            )}

            <Dialog
                open={modal.open}
                onClose={requestClose}
                title={modal.address ? __('Edit address') : __('Add new address')}
            >
                <AccountAddressForm
                    key={modal.address?.id ?? 'new'}
                    address={modal.address}
                    onSuccess={closeModal}
                    onCancel={requestClose}
                    onDirtyChange={setDirty}
                />
            </Dialog>

            <Dialog open={deleting !== null} onClose={() => setDeleting(null)} title={__('Delete address')}>
                <p className="mt-0 mb-0 leading-relaxed text-ink">
                    {__('Are you sure you want to delete this address?')}
                </p>
                <div className="mt-5 flex justify-end gap-3">
                    <Button variant="outline" size="md" onClick={() => setDeleting(null)}>
                        {__('No, keep it')}
                    </Button>
                    <Button variant="error" size="md" processing={deleteProcessing} onClick={confirmDelete}>
                        {__('Yes, delete')}
                    </Button>
                </div>
            </Dialog>
        </>
    );
}

function AddButton({ onClick }: { onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="flex min-h-[140px] flex-col items-center justify-center gap-2 rounded-md border border-dashed border-line-strong p-5 text-sm font-semibold text-muted transition-colors can-hover:hover:border-primary can-hover:hover:text-primary"
        >
            <PlusIcon size={24} strokeWidth={1.8} aria-hidden="true" />
            {__('Add new address')}
        </button>
    );
}

Addresses.title = __('Addresses');
