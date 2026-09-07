<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\StoreCustomerAction;
use App\Enums\TokenAbility;
use App\Http\Requests\Api\V1\StoreRegistrationRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterController
{
    public function store(StoreRegistrationRequest $request, StoreCustomerAction $action): JsonResponse
    {
        $user = $action->handle($request->toDto());

        event(new Registered($user));

        $token = $user->createToken(
            $request->safe()->string('device_name')->value(),
            [TokenAbility::Customer->value],
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }
}
