import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { Switch } from '@/components/ui/switch';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import { cn } from '@/lib/utils';

interface SwitchSettingProps {
    label: string;
    description: string;
    checked: boolean;
    onCheckedChange?: (checked: boolean) => void;
    disabled?: boolean;
    permission?: Permission;
    pro?: boolean;
}

export function SwitchSetting({
    label,
    description,
    checked,
    onCheckedChange,
    disabled,
    permission = Permission.StorefrontUpdate,
    pro = false,
}: SwitchSettingProps) {
    const { hasPermission } = usePermissions();
    const { open: openProUpgrade } = useProUpgrade();
    const isDisabled = pro || disabled || !hasPermission(permission);

    const handleClick = () => {
        if (pro) {
            openProUpgrade(label);

            return;
        }

        if (!isDisabled && onCheckedChange) {
            onCheckedChange(!checked);
        }
    };

    return (
        <div
            className={cn('flex items-start justify-between gap-4', pro && 'cursor-pointer')}
            onClick={pro ? handleClick : undefined}
            role={pro ? 'presentation' : undefined}
        >
            <div className="space-y-1 text-sm">
                <p
                    className={cn(
                        'flex items-center gap-2 font-medium',
                        !isDisabled && onCheckedChange && 'cursor-default',
                    )}
                    onClick={pro ? undefined : handleClick}
                >
                    {label}
                    {pro && <ProBadge />}
                </p>
                <p className="text-muted-foreground">{description}</p>
            </div>
            <Switch
                checked={checked}
                onCheckedChange={pro ? undefined : onCheckedChange}
                disabled={isDisabled}
                aria-label={pro ? __('Available in Pro') : undefined}
            />
        </div>
    );
}
