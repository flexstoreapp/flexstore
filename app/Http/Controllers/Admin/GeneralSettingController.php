<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateGeneralSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GeneralSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/general', [
            'settings' => Setting::getByGroup(SettingGroup::General),
        ]);
    }

    public function update(UpdateGeneralSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
