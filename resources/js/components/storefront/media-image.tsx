import { ImagePlaceholder } from '@/components/storefront/image-placeholder';
import { useImageFallback } from '@/hooks/storefront/use-image-fallback';
import { mediaFull, mediaSmallThumb, mediaThumb } from '@/lib/media';
import { cn } from '@/lib/utils';
import type { MediaItem } from '@/types/media';

export const imageZoom =
    'duration-(--duration-slow) ease-out-quart can-hover:group-hover:scale-105 transition-transform';

export interface MediaImageProps {
    media: MediaItem | null | undefined;
    source?: 'small' | 'thumbnail' | 'full';
    alt?: string;
    fit?: 'cover' | 'contain';
    loading?: 'lazy' | 'eager';
    className?: string;
    placeholderClassName?: string;
}

export function MediaImage({
    media,
    source = 'thumbnail',
    alt = '',
    fit = 'cover',
    loading = 'lazy',
    className,
    placeholderClassName,
}: MediaImageProps) {
    const url = source === 'full' ? mediaFull(media) : source === 'small' ? mediaSmallThumb(media) : mediaThumb(media);
    const { failed, imgProps } = useImageFallback(url);

    if (failed) {
        return <ImagePlaceholder className={placeholderClassName} />;
    }

    return (
        <img
            {...imgProps}
            src={url ?? undefined}
            alt={alt}
            loading={loading}
            decoding="async"
            className={cn(
                fit === 'contain' ? 'h-auto max-h-full w-auto max-w-full object-contain' : 'h-full w-full object-cover',
                className,
            )}
        />
    );
}
