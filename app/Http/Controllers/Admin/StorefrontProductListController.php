<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\Admin\UpdateStorefrontProductListSettingsRequest;
use App\Queries\ProductListSettingsQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontProductListController
{
    public function edit(ProductListSettingsQuery $settingsQuery): Response
    {
        return Inertia::render('admin/storefront/product-list', [
            'settings' => $settingsQuery->execute(),
        ]);
    }

    public function update(UpdateStorefrontProductListSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
