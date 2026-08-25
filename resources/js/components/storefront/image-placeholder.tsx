import { ImageIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

interface ImagePlaceholderProps {
    className?: string;
    iconClassName?: string;
}

export function ImagePlaceholder({ className, iconClassName }: ImagePlaceholderProps) {
    return (
        <div className={cn('flex h-full w-full items-center justify-center bg-surface-2 text-line-strong', className)}>
            <ImageIcon className={cn('size-1/3', iconClassName)} strokeWidth={1.5} aria-hidden="true" />
        </div>
    );
}
