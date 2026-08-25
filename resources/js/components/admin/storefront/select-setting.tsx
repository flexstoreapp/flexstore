import { AdaptiveSelect, type AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { Permission } from '@/lib/permissions';

interface SelectSettingProps {
    label: string;
    description: string;
    options: AdaptiveSelectOption[];
    value: string;
    onValueChange?: (value: string) => void;
    disabled?: boolean;
    permission?: Permission;
    className?: string;
}

export function SelectSetting({
    label,
    description,
    options,
    value,
    onValueChange,
    disabled,
    permission = Permission.StorefrontUpdate,
    className,
}: SelectSettingProps) {
    const { hasPermission } = usePermissions();
    const isDisabled = disabled || !hasPermission(permission);

    return (
        <div className="space-y-4">
            <div className="space-y-1 text-sm">
                <p className="font-medium">{label}</p>
                <p className="text-muted-foreground">{description}</p>
            </div>
            <AdaptiveSelect
                options={options}
                value={value}
                onValueChange={onValueChange}
                disabled={isDisabled}
                className={className}
            />
        </div>
    );
}
