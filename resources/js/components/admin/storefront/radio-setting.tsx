import { useId } from 'react';

import { RadioCard, RadioCardGroup } from '@/components/ui/radio-card';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { Permission } from '@/lib/permissions';

interface RadioOption {
    value: string;
    label: string;
    description: string;
}

interface RadioSettingProps {
    label: string;
    description: string;
    options: RadioOption[];
    value: string;
    onValueChange?: (value: string) => void;
    disabled?: boolean;
    permission?: Permission;
}

export function RadioSetting({
    label,
    description,
    options,
    value,
    onValueChange,
    disabled,
    permission = Permission.StorefrontUpdate,
}: RadioSettingProps) {
    const { hasPermission } = usePermissions();
    const isDisabled = disabled || !hasPermission(permission);
    const labelId = useId();

    return (
        <div className="space-y-4">
            <div className="space-y-1 text-sm">
                <p id={labelId} className="font-medium">
                    {label}
                </p>
                <p className="text-muted-foreground">{description}</p>
            </div>
            <RadioCardGroup
                value={value}
                onValueChange={(next) => onValueChange?.(next)}
                readonly={isDisabled}
                aria-labelledby={labelId}
            >
                {options.map((option) => (
                    <RadioCard
                        key={option.value}
                        value={option.value}
                        label={option.label}
                        description={option.description}
                    />
                ))}
            </RadioCardGroup>
        </div>
    );
}
