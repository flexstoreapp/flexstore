export type MediaType = 'image' | 'video' | 'file';

export interface MediaDimensions {
    width?: number | null;
    height?: number | null;
}

export interface MediaItem extends MediaDimensions {
    id: number;
    type: MediaType;
    url: string | null;
    thumbnail_url: string | null;
    small_thumbnail_url?: string | null;
    alt?: string | null;
    mime_type?: string | null;
    size?: number | null;
    duration?: number | null;
    original_filename?: string | null;
}
