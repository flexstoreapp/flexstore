<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Media;
use App\Models\StorefrontSection;

final readonly class DestroyStorefrontSectionAction
{
    public function handle(StorefrontSection $section): bool
    {
        $mediaIds = $section->media()->pluck('media.id')->all();

        $deleted = $section->delete() ?? false;

        Media::deleteUnreferenced($mediaIds);

        return $deleted;
    }
}
