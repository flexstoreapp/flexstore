import { CheckIcon } from 'lucide-react';
import { RadioGroup as RadioGroupPrimitive } from 'radix-ui';
import * as React from 'react';

import { cn } from '@/lib/utils';

const RadioCardContext = React.createContext<{ readonly: boolean }>({ readonly: false });

interface RadioCardGroupProps extends React.ComponentProps<typeof RadioGroupPrimitive.Root> {
    readonly?: boolean;
}

export function RadioCardGroup({ className, readonly = false, disabled, ...props }: RadioCardGroupProps) {
    return (
        <RadioCardContext.Provider value={{ readonly }}>
            <RadioGroupPrimitive.Root
                data-slot="radio-card-group"
                disabled={disabled || readonly}
                aria-readonly={readonly || undefined}
                className={cn("grid gap-3", className)}
                {...props}
            />
        </RadioCardContext.Provider>
    );
}

interface RadioCardProps extends React.ComponentProps<typeof RadioGroupPrimitive.Item> {
    label?: string;
    description?: string;
}

export function RadioCard({ label, description, className, children, ...props }: RadioCardProps) {
    const { readonly } = React.useContext(RadioCardContext);

    return (
        <RadioGroupPrimitive.Item
            data-slot="radio-card"
            className={cn(
                "group/radio-card w-full rounded-lg border bg-background p-4 text-start outline-none transition-all",
                "focus-visible:ring-[3px] focus-visible:ring-ring/50",
                "not-disabled:data-[state=unchecked]:hover:bg-muted/50",
                "data-[state=checked]:border-primary data-[state=checked]:bg-muted/30",
                "disabled:pointer-events-none",
                readonly ? "disabled:cursor-default" : "disabled:cursor-not-allowed disabled:opacity-50",
                className,
            )}
            {...props}
        >
            {children ?? (
                <div className="flex items-start justify-between gap-4 text-sm">
                    <div className="space-y-1">
                        <p className="font-medium">{label}</p>
                        <p className="text-muted-foreground">{description}</p>
                    </div>
                    <div className="flex size-5 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary opacity-0 transition-opacity group-data-[state=checked]/radio-card:opacity-100">
                        <CheckIcon className="size-3 text-primary-foreground" />
                    </div>
                </div>
            )}
        </RadioGroupPrimitive.Item>
    );
}
