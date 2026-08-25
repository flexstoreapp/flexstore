<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateMailSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MailSettingController
{
    public function show(): Response
    {
        $settings = Setting::getByGroup(SettingGroup::Mail);

        if (blank($settings->get('mail_from_address'))) {
            $settings->put('mail_from_address', Setting::getValue('store_email'));
        }

        if (blank($settings->get('mail_from_name'))) {
            $settings->put('mail_from_name', Setting::getValue('store_name'));
        }

        return Inertia::render('admin/settings/mail', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateMailSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
