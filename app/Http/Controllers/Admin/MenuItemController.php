<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyMenuItemAction;
use App\Actions\StoreMenuItemAction;
use App\Actions\UpdateMenuItemAction;
use App\Enums\MenuLocation;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MenuItemController
{
    public function create(Request $request): Response
    {
        return Inertia::render('admin/storefront/menu-items/create', [
            'location' => $request->query('location', MenuLocation::Header->value),
        ]);
    }

    public function store(StoreMenuItemRequest $request, StoreMenuItemAction $action): RedirectResponse
    {
        $menuItem = $action->handle($request->toDto());

        return to_route('admin.storefront.menu-items.edit', $menuItem);
    }

    public function edit(MenuItem $menuItem): Response
    {
        return Inertia::render('admin/storefront/menu-items/edit', [
            'menuItem' => $menuItem->load(['brand', 'category', 'featuredImage']),
            'location' => $menuItem->location->value,
        ]);
    }

    public function update(
        UpdateMenuItemRequest $request,
        MenuItem $menuItem,
        UpdateMenuItemAction $action
    ): RedirectResponse {
        $action->handle($menuItem, $request->toDto());

        return back();
    }

    public function destroy(MenuItem $menuItem, DestroyMenuItemAction $action): RedirectResponse
    {
        $action->handle($menuItem);

        return back();
    }
}
