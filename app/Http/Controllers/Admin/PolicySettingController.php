<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdatePolicySettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PolicySettingController
{
    public function show(): Response
    {
        return Inertia::render('admin/settings/policy', [
            'settings' => Setting::getByGroup(SettingGroup::Policy),
        ]);
    }

    public function update(UpdatePolicySettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
