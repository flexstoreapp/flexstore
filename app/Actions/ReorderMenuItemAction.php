<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

final readonly class ReorderMenuItemAction
{
    public function handle(MenuItem $menuItem, ?int $newParentId, int $position): MenuItem
    {
        return DB::transaction(function () use ($menuItem, $newParentId, $position): MenuItem {
            if ($menuItem->parent_id !== $newParentId) {
                $menuItem->update(['parent_id' => $newParentId]);
            }

            $siblings = MenuItem::query()
                ->where('location', $menuItem->location)
                ->where('parent_id', $newParentId)
                ->where('id', '!=', $menuItem->id)
                ->orderBy('sort_order')
                ->get();

            $siblings->splice($position, 0, [$menuItem]);

            foreach ($siblings as $index => $sibling) {
                $sibling->update(['sort_order' => $index]);
            }

            $fresh = $menuItem->fresh();
            assert($fresh instanceof MenuItem);

            return $fresh;
        });
    }
}
