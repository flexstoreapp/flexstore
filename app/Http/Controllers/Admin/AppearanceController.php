<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateUserAction;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class AppearanceController
{
    public function update(
        UpdateAppearanceRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action,
    ): RedirectResponse {
        $action->handle($user->refresh(), $request->toDto());

        return back();
    }
}
