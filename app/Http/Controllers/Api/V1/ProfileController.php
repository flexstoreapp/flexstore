<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteCustomerAccountAction;
use App\Actions\UpdateUserAction;
use App\Enums\Role;
use App\Http\Requests\Api\V1\DestroyProfileRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class ProfileController
{
    public function show(#[CurrentUser] User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(
        UpdateProfileRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action,
    ): UserResource {
        $originalEmail = $user->email;

        $action->handle($user->refresh(), $request->toDto());

        if ($user->email !== $originalEmail) {
            $user->sendEmailVerificationNotification();
        }

        return new UserResource($user->refresh());
    }

    public function destroy(
        DestroyProfileRequest $request,
        #[CurrentUser] User $user,
        DeleteCustomerAccountAction $action,
    ): JsonResponse {
        abort_if($user->hasRole(Role::SuperAdmin), 403);

        $user->tokens()->delete();

        $action->handle($user);

        return response()->json(['message' => __('Your account has been deleted.')]);
    }
}
