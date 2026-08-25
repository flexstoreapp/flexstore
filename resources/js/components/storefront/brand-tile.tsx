import { Link } from '@inertiajs/react';

import * as BrandController from '@/actions/App/Http/Controllers/Storefront/BrandController';
import * as BrandProductController from '@/actions/App/Http/Controllers/Storefront/BrandProductController';
import { imageZoom, MediaImage } from '@/components/storefront/media-image';
import { cn, getTranslation } from '@/lib/utils';
import type { TranslatableField } from '@/types';
import type { MediaItem } from '@/types/media';

interface BrandTileProps {
    name: TranslatableField;
    urlHandle?: string;
    image?: MediaItem | null;
    grayscale?: boolean;
}

export function BrandTile({ name, urlHandle, image, grayscale }: BrandTileProps) {
    const label = getTranslation(name);

    return (
        <Link
            href={urlHandle ? BrandProductController.show(urlHandle) : BrandController.index()}
            aria-label={label}
            className="group flex aspect-[2/1] items-center justify-center overflow-hidden border-e border-b border-line p-6 sm:p-8"
        >
            <MediaImage
                media={image}
                fit="contain"
                className={cn(imageZoom, grayscale && 'grayscale can-hover:group-hover:grayscale-0')}
                placeholderClassName="h-full w-full"
            />
        </Link>
    );
}
