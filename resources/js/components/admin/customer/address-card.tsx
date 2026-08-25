import { EditIcon, StarIcon, Trash2Icon } from 'lucide-react';

import { HoverActions } from '@/components/admin/hover-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';
import { type CustomerAddress } from '@/types';

interface AddressCardProps {
    address: CustomerAddress;
    onSetDefault: (address: CustomerAddress) => void;
    onEdit: (address: CustomerAddress) => void;
    onDelete: (address: CustomerAddress) => void;
}

export function AddressCard({ address, onSetDefault, onEdit, onDelete }: AddressCardProps) {
    const { countryNames } = useCountries();

    return (
        <Card className="group/item gap-0 py-0">
            <CardContent className="flex items-start justify-between gap-2 p-4">
                <div className="flex-1 space-y-1">
                    <div className="flex items-center gap-2">
                        {address.first_name} {address.last_name}
                        {address.is_default && <Badge variant="secondary">{__('Default')}</Badge>}
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {[
                            address.address_line_1,
                            address.address_line_2,
                            address.city,
                            address.state_name ?? address.state,
                            address.postal_code,
                            countryNames[address.country_code] ?? address.country_code,
                        ]
                            .filter(Boolean)
                            .join(', ')}
                    </p>
                    {address.phone && (
                        <p className="text-sm text-muted-foreground">
                            <a href={`tel:${address.phone}`} className="hover:underline">
                                <bdi>{address.phone}</bdi>
                            </a>
                        </p>
                    )}
                </div>

                <HoverActions>
                    {!address.is_default && (
                        <Button type="button" variant="ghost" size="icon-md" onClick={() => onSetDefault(address)}>
                            <StarIcon />
                            <span className="sr-only">{__('Set default')}</span>
                        </Button>
                    )}
                    <Button type="button" variant="ghost" size="icon-md" onClick={() => onEdit(address)}>
                        <EditIcon />
                        <span className="sr-only">{__('Edit')}</span>
                    </Button>
                    <Button type="button" variant="ghost" size="icon-md" onClick={() => onDelete(address)}>
                        <Trash2Icon />
                        <span className="sr-only">{__('Delete')}</span>
                    </Button>
                </HoverActions>
            </CardContent>
        </Card>
    );
}
