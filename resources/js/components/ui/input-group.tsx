import * as React from 'react';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface InputGroupContextValue {
    hasPrefix: boolean;
    hasSuffix: boolean;
    hasSelect: boolean;
}

const InputGroupContext = React.createContext<InputGroupContextValue>({
    hasPrefix: false,
    hasSuffix: false,
    hasSelect: false,
});

interface InputGroupRootProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

function InputGroupRoot({ className, children, ref, ...props }: InputGroupRootProps & { ref?: React.Ref<HTMLDivElement> }) {
    const contextValue = React.useMemo<InputGroupContextValue>(() => {
        const childrenArray = React.Children.toArray(children);

        return {
            hasPrefix: childrenArray.some((child) => React.isValidElement(child) && child.type === InputGroupPrefix),
            hasSuffix: childrenArray.some((child) => React.isValidElement(child) && child.type === InputGroupSuffix),
            hasSelect: childrenArray.some((child) => React.isValidElement(child) && child.type === InputGroupSelect),
        };
    }, [children]);

    return (
        <InputGroupContext.Provider value={contextValue}>
            <div
                ref={ref}
                className={cn(
                    "group flex h-9 items-center rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50",
                    className,
                )}
                {...props}
            >
                {children}
            </div>
        </InputGroupContext.Provider>
    );
}

interface InputGroupControlProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'className'> {
    className?: string;
}

function InputGroupControl({ className, ref, ...props }: InputGroupControlProps & { ref?: React.Ref<HTMLInputElement> }) {
    const { hasPrefix, hasSuffix, hasSelect } = React.useContext(InputGroupContext);

    return (
        <Input
            ref={ref}
            className={cn(
                "border-0 shadow-none ring-0 focus-visible:border-0 focus-visible:ring-0 focus-visible:ring-offset-0",
                hasPrefix && "ps-2!",
                hasSuffix && "pe-1.5!",
                !hasSuffix && !hasSelect && "pe-3!",
                hasSelect && "pe-0!",
                className,
            )}
            {...props}
        />
    );
}

interface InputGroupPrefixProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

function InputGroupPrefix({ className, children, ref, ...props }: InputGroupPrefixProps & { ref?: React.Ref<HTMLDivElement> }) {
    return (
        <div ref={ref} className={cn("shrink-0 ps-3 text-sm text-muted-foreground select-none", className)} {...props}>
            {children}
        </div>
    );
}

interface InputGroupSuffixProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

function InputGroupSuffix({ className, children, ref, ...props }: InputGroupSuffixProps & { ref?: React.Ref<HTMLDivElement> }) {
    return (
        <div ref={ref} className={cn("shrink-0 pe-3 text-sm text-muted-foreground select-none", className)} {...props}>
            {children}
        </div>
    );
}

function InputGroupSelect({ className, ref, ...props }: React.ComponentProps<typeof AdaptiveSelect> & { ref?: React.Ref<HTMLButtonElement> }) {
    return (
        <AdaptiveSelect
            ref={ref}
            className={cn(
                "h-auto min-h-0 w-auto min-w-0 rounded-none border-0 px-2 py-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 has-[>svg]:px-2",
                className,
            )}
            contentClassName="w-auto"
            {...props}
        />
    );
}

const InputGroup = {
    Root: InputGroupRoot,
    Prefix: InputGroupPrefix,
    Control: InputGroupControl,
    Suffix: InputGroupSuffix,
    Select: InputGroupSelect,
};

export { InputGroup };
