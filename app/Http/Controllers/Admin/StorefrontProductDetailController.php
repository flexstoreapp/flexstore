<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\Admin\UpdateStorefrontProductDetailRequest;
use App\Queries\ProductDetailSettingsQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontProductDetailController
{
    public function edit(ProductDetailSettingsQuery $settingsQuery): Response
    {
        return Inertia::render('admin/storefront/product-detail', [
            'settings' => $settingsQuery->execute(),
        ]);
    }

    public function update(UpdateStorefrontProductDetailRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
