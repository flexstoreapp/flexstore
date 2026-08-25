import type { CartItem } from '@/types';
import type { MediaDimensions, MediaItem } from '@/types/media';

export function mediaThumb(media: MediaItem | null | undefined): string | null {
    return media?.thumbnail_url ?? media?.url ?? null;
}

export function mediaSmallThumb(media: MediaItem | null | undefined): string | null {
    return media?.small_thumbnail_url ?? media?.thumbnail_url ?? media?.url ?? null;
}

export function mediaFull(media: MediaItem | null | undefined): string | null {
    return media?.url ?? media?.thumbnail_url ?? null;
}

export function mediaAlt(media: MediaItem | null | undefined, fallback: string): string {
    return media?.alt ?? fallback;
}

export function lineItemMedia(item: CartItem): MediaItem | null {
    return item.product_variant?.media ?? item.product?.featured_media ?? null;
}

export function galleryMedia(media: MediaItem[], variantMedia: MediaItem | null | undefined): MediaItem[] {
    if (variantMedia && !media.some((item) => item.id === variantMedia.id)) {
        return [variantMedia, ...media];
    }

    return media;
}

export function mediaRatio(media: MediaDimensions | null | undefined): number | null {
    if (!media?.width || !media.height) return null;

    return media.width / media.height;
}

const MIN_BOX_RATIO = 3 / 4;
const MAX_BOX_RATIO = 4 / 3;

export function mediaBoxRatio(media: (MediaDimensions | null | undefined)[]): number {
    for (const item of media) {
        const ratio = mediaRatio(item);

        if (ratio !== null) {
            return Math.min(MAX_BOX_RATIO, Math.max(MIN_BOX_RATIO, ratio));
        }
    }

    return 1;
}
