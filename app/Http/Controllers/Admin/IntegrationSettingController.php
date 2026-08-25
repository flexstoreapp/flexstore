<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateIntegrationSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class IntegrationSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/integration', [
            'settings' => Setting::getByGroup(SettingGroup::Integration),
        ]);
    }

    public function update(UpdateIntegrationSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
