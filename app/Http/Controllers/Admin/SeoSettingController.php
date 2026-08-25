<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateSeoSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SeoSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/seo', [
            'settings' => Setting::getByGroup(SettingGroup::Seo),
        ]);
    }

    public function update(UpdateSeoSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
