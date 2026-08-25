import { ImageIcon } from 'lucide-react';
import { createContext, useContext, type ReactNode } from 'react';

import { mediaBoxRatio } from '@/lib/media';
import { cn } from '@/lib/utils';
import type { MediaDimensions } from '@/types/media';

const ThumbnailRatioContext = createContext(1);

interface ThumbnailRatioProps {
    media: (MediaDimensions | null | undefined)[];
    children: ReactNode;
}

export function ThumbnailRatio({ media, children }: ThumbnailRatioProps) {
    return <ThumbnailRatioContext.Provider value={mediaBoxRatio(media)}>{children}</ThumbnailRatioContext.Provider>;
}

interface ThumbnailProps {
    src?: string | null;
    alt: string;
    fallback?: ReactNode;
    className?: string;
}

export function Thumbnail({ src, alt, fallback, className }: ThumbnailProps) {
    const ratio = useContext(ThumbnailRatioContext);

    return (
        <div
            style={{ aspectRatio: ratio }}
            className={cn('relative h-10 shrink-0 overflow-hidden rounded-md border bg-muted', className)}
        >
            {src ? (
                <img src={src} alt={alt} className="absolute inset-0 size-full object-contain" loading="lazy" />
            ) : (
                <div className="inline-flex size-full items-center justify-center text-muted-foreground/60">
                    {fallback ?? <ImageIcon className="size-4" />}
                </div>
            )}
        </div>
    );
}
