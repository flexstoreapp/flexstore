<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateNotificationSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationSettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/notification', [
            'settings' => Setting::getByGroup(SettingGroup::Notification),
        ]);
    }

    public function update(UpdateNotificationSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
