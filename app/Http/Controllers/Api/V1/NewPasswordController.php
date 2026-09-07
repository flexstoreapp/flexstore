<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResetUserPasswordAction;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final readonly class NewPasswordController
{
    public function store(ResetPasswordRequest $request, ResetUserPasswordAction $action): JsonResponse
    {
        $status = $action->handle($request->toDto());

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }
}
