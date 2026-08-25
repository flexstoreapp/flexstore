import { memo, useMemo } from 'react';

import { Badge } from '@/components/ui/badge';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Region } from '@/types';

interface RegionRowProps {
    region: Region;
    isSelected: boolean;
    onSelectRegion: (regionId: number, shiftKey?: boolean) => void;
    onEdit: (region: Region) => void;
}

export const RegionRow = memo(({ region, isSelected, onSelectRegion, onEdit }: RegionRowProps) => {
    const { countryNames } = useCountries();
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.RegionsManage);
    const canDelete = hasPermission(Permission.RegionsDelete);
    const showCheckbox = canUpdate || canDelete;

    const handleRowClick = () => {
        if (canUpdate) {
            onEdit(region);
        }
    };

    const handleSelectRegion = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectRegion(region.id, e.shiftKey);
    };

    const tableRowClass = useMemo(() => {
        if (!canUpdate) return 'cursor-default';
        return showCheckbox ? '[&>td:not(:first-child)]:cursor-pointer' : 'cursor-pointer';
    }, [canUpdate, showCheckbox]);

    return (
        <TableRow
            key={region.id}
            data-state={isSelected && 'selected'}
            className={tableRowClass}
            onClick={handleRowClick}
        >
            <Can permissions={[Permission.RegionsManage, Permission.RegionsDelete]}>
                <TableCell onClick={handleSelectRegion} className="w-10">
                    <Checkbox
                        checked={isSelected}
                        aria-label={__('Select :name', { name: getTranslation(region.name) })}
                    />
                </TableCell>
            </Can>

            <TableCell className="font-medium">{getTranslation(region.name)}</TableCell>
            <TableCell>
                {region.countries && region.countries.length > 0 ? (
                    <div className="flex items-center gap-1.5">
                        {region.countries.slice(0, 2).map((code) => (
                            <Badge key={code} variant="outline">
                                {countryNames[code]}
                            </Badge>
                        ))}
                        {region.countries.length > 2 && (
                            <Badge dir="ltr" variant="outline">
                                +{region.countries.length - 2}
                            </Badge>
                        )}
                    </div>
                ) : (
                    '—'
                )}
            </TableCell>
            <TableCell>
                {region.states && region.states.length > 0 ? (
                    <div className="flex items-center gap-1.5">
                        {region.states.slice(0, 2).map((state) => (
                            <Badge key={state} variant="outline">
                                {state}
                            </Badge>
                        ))}
                        {region.states.length > 2 && (
                            <Badge dir="ltr" variant="outline">
                                +{region.states.length - 2}
                            </Badge>
                        )}
                    </div>
                ) : (
                    '—'
                )}
            </TableCell>
            <TableCell>
                {region.postal_codes && region.postal_codes.length > 0 ? (
                    <div className="flex items-center gap-1.5">
                        {region.postal_codes.slice(0, 2).map((postal_code) => (
                            <Badge key={postal_code} variant="outline">
                                {postal_code}
                            </Badge>
                        ))}
                        {region.postal_codes.length > 2 && (
                            <Badge dir="ltr" variant="outline">
                                +{region.postal_codes.length - 2}
                            </Badge>
                        )}
                    </div>
                ) : (
                    '—'
                )}
            </TableCell>
        </TableRow>
    );
});
RegionRow.displayName = 'RegionRow';
