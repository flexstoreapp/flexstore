<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SystemSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/system', [
            'settings' => Setting::getByGroup(SettingGroup::System)->except(['system_checksum', 'system_registry']),
            'currentVersion' => (string) (Setting::getValue('flexstore_version') ?? '1.0.0'),
            'updateHistory' => [],
            'pendingUpdate' => null,
            'latestVersion' => null,
            'portalUrl' => null,
            'license' => null,
        ]);
    }

    public function update(UpdateSystemSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
