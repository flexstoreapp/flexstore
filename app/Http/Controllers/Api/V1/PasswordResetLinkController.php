<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\SendPasswordResetLinkAction;
use App\Http\Requests\SendPasswordResetLinkRequest;
use Illuminate\Http\JsonResponse;

final readonly class PasswordResetLinkController
{
    public function store(SendPasswordResetLinkRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->handle($request->toDto());

        return response()->json(['message' => __('A reset link will be sent if the account exists.')]);
    }
}
