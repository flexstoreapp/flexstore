import { Checkbox } from './checkbox';
import { HelpBlock } from './help-block';
import { Label } from './label';
import { cn } from '@/lib/utils';

interface CheckboxWithLabelProps {
    id: string;
    name?: string;
    label: string;
    description?: string;
    checked?: boolean;
    defaultChecked?: boolean;
    onCheckedChange?: (checked: boolean) => void;
    className?: string;
    disabled?: boolean;
}

export function CheckboxCard({ id, name, label, description, checked, defaultChecked = false, onCheckedChange, className, disabled = false }: CheckboxWithLabelProps) {
    return (
        <Label
            htmlFor={id}
            className={cn(
                "flex items-center justify-between rounded-lg border p-4 shadow-xs gap-4",
                disabled && "cursor-not-allowed opacity-50",
                className,
            )}
        >
            <div className="space-y-1">
                <div className="text-sm font-medium">{label}</div>
                {description && <HelpBlock className="font-normal">{description}</HelpBlock>}
            </div>
            <Checkbox
                id={id}
                name={name}
                {...(checked !== undefined ? { checked } : { defaultChecked })}
                onCheckedChange={(value) => onCheckedChange?.(value === true)}
                disabled={disabled}
            />
        </Label>
    );
}
