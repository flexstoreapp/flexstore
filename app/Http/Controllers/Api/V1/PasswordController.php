<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\UpdateUserAction;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class PasswordController
{
    public function update(
        UpdatePasswordRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action,
    ): JsonResponse {
        $action->handle($user, $request->toDto());

        return response()->json(['message' => __('Your password has been updated.')]);
    }
}
