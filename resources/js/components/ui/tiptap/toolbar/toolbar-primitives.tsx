import { Toolbar } from 'radix-ui';

import { toggleVariants } from '@/components/ui/toggle';
import { cn } from '@/lib/utils';

export function ToolbarToggleGroup({ className, ...props }: React.ComponentProps<typeof Toolbar.ToggleGroup>) {
    return <Toolbar.ToggleGroup className={cn("flex shrink-0 items-center", className)} {...props} />;
}

export function ToolbarToggleItem({ className, ...props }: React.ComponentProps<typeof Toolbar.ToggleItem>) {
    return <Toolbar.ToggleItem className={cn(toggleVariants(), "shrink-0", className)} {...props} />;
}

export function ToolbarSeparator({ className, ...props }: React.ComponentProps<typeof Toolbar.Separator>) {
    return <Toolbar.Separator className={cn("bg-border h-6 w-px shrink-0", className)} {...props} />;
}
