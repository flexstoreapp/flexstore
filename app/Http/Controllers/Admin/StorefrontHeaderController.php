<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateStorefrontHeaderAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateStorefrontHeaderRequest;
use App\Models\Setting;
use App\Queries\AdminHeaderBrowseCategoriesQuery;
use App\Queries\AdminHeaderMenuItemListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontHeaderController
{
    public function edit(
        AdminHeaderMenuItemListQuery $menuItemQuery,
        AdminHeaderBrowseCategoriesQuery $browseCategoriesQuery,
    ): Response {
        $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);

        return Inertia::render('admin/storefront/header', [
            'menuItems' => $menuItemQuery->execute(),
            'browseCategories' => $browseCategoriesQuery->execute(),
            'settings' => [
                'sticky' => $storefrontSettings->get('storefront_header_sticky', false),
            ],
        ]);
    }

    public function update(UpdateStorefrontHeaderRequest $request, UpdateStorefrontHeaderAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
