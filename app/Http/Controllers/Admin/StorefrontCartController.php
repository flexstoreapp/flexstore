<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\Admin\UpdateStorefrontCartRequest;
use App\Queries\CartSettingsQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontCartController
{
    public function edit(CartSettingsQuery $settingsQuery): Response
    {
        return Inertia::render('admin/storefront/cart', [
            'settings' => $settingsQuery->execute(),
        ]);
    }

    public function update(UpdateStorefrontCartRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
