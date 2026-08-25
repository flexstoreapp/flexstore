import { MediaImage } from '@/components/storefront/media-image';
import { cn } from '@/lib/utils';
import type { MediaItem } from '@/types/media';

interface ContainedMediaProps {
    media: MediaItem | null | undefined;
    alt?: string;
    source?: 'small' | 'thumbnail' | 'full';
    loading?: 'lazy' | 'eager';
    className?: string;
}

export function ContainedMedia({
    media,
    alt = '',
    source = 'thumbnail',
    loading = 'lazy',
    className,
}: ContainedMediaProps) {
    return (
        <MediaImage
            media={media}
            source={source}
            fit="contain"
            loading={loading}
            alt={alt}
            className={cn('absolute inset-0 m-auto', className)}
            placeholderClassName="size-full"
        />
    );
}
