<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateStorefrontThemeRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontThemeController
{
    public function edit(): Response
    {
        $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);

        return Inertia::render('admin/storefront/theme', [
            'storefrontThemeColor' => $storefrontSettings->get('storefront_theme_color', 'blue'),
        ]);
    }

    public function update(UpdateStorefrontThemeRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
