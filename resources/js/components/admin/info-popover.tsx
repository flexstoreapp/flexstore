import { InfoIcon } from 'lucide-react';

import { useDirection } from '@/components/ui/direction';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export function InfoPopover({ text, className }: { text: string; className?: string }) {
    const direction = useDirection();
    const side = direction === 'rtl' ? 'left' : 'right';

    return (
        <Popover>
            <PopoverTrigger asChild>
                <InfoIcon className="size-3.5 text-muted-foreground" />
            </PopoverTrigger>
            <PopoverContent className={cn('text-sm text-muted-foreground', className)} side={side} align="start">
                {text}
            </PopoverContent>
        </Popover>
    );
}
