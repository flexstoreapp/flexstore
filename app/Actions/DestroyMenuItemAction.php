<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Media;
use App\Models\MenuItem;

final readonly class DestroyMenuItemAction
{
    public function handle(MenuItem $menuItem): bool
    {
        $children = MenuItem::query()->where('parent_id', $menuItem->id)->get(['id', 'featured_image_id']);
        $children->each->delete();

        $deleted = (bool) $menuItem->delete();

        Media::deleteUnreferenced([
            $menuItem->featured_image_id,
            ...$children->pluck('featured_image_id'),
        ]);

        return $deleted;
    }
}
