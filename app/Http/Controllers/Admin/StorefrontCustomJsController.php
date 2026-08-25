<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\Admin\UpdateStorefrontCustomJsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontCustomJsController
{
    public function edit(): Response
    {
        return Inertia::render('admin/storefront/custom-js', [
            'customJs' => Setting::getValue('storefront_custom_js', ''),
        ]);
    }

    public function update(UpdateStorefrontCustomJsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
