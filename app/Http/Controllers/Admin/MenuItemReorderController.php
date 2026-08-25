<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderMenuItemAction;
use App\Http\Requests\Admin\ReorderMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;

final readonly class MenuItemReorderController
{
    public function update(
        ReorderMenuItemRequest $request,
        MenuItem $menuItem,
        ReorderMenuItemAction $action
    ): RedirectResponse {
        $parentId = $request->validated('parent_id');
        $action->handle(
            $menuItem,
            $parentId !== null ? (int) $parentId : null,
            $request->safe()->integer('position')
        );

        return back();
    }
}
