<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\MenuLocation;
use App\Models\Media;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class AdminFooterMenuItemListQuery
{
    /**
     * @return Collection<int, MenuItem>
     */
    public function execute(): Collection
    {
        return MenuItem::query()
            ->forLocation(MenuLocation::Footer)
            ->with([
                'children' => fn (Relation $query): Relation => $query->orderBy('sort_order'),
                'children.brand.image:' . Media::displaySelect(),
                'children.category',
                'brand.image:' . Media::displaySelect(),
                'category',
            ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }
}
