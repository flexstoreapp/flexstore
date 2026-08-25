import { XIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface RemovableTagProps extends React.ComponentProps<'div'> {
    label: string;
    onRemove: () => void;
    className?: string;
}

export function RemovableTag({ label, onRemove, className, ...props }: RemovableTagProps) {
    return (
        <div
            className={cn("group/tag relative flex items-center rounded-md bg-muted px-2 py-1 text-sm", className)}
            {...props}
        >
            <span>{label}</span>
            <button
                type="button"
                onClick={onRemove}
                className={cn(
                    "absolute -top-1 -end-1 flex size-5 items-center justify-center border-2 border-background",
                    "rounded-full bg-primary text-primary-foreground transition-opacity duration-150 hover:bg-primary/90",
                    "[@media(hover:hover)]:pointer-events-none [@media(hover:hover)]:opacity-0",
                    "[@media(hover:hover)]:group-hover/tag:pointer-events-auto [@media(hover:hover)]:group-hover/tag:opacity-100",
                )}
            >
                <XIcon className="size-3" />
                <span className="sr-only">{__('Remove :name', { name: label })}</span>
            </button>
        </div>
    );
}
