<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\Admin\UpdateStorefrontCustomCssRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontCustomCssController
{
    public function edit(): Response
    {
        return Inertia::render('admin/storefront/custom-css', [
            'customCss' => Setting::getValue('storefront_custom_css', ''),
        ]);
    }

    public function update(UpdateStorefrontCustomCssRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
