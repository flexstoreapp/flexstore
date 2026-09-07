<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\StoreCustomerAddressAction;
use App\Actions\UpdateCustomerAddressAction;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class AddressController
{
    public function index(#[CurrentUser] User $user, CustomerAddressListQuery $query): AnonymousResourceCollection
    {
        return CustomerAddressResource::collection($query->execute($user));
    }

    public function store(
        StoreCustomerAddressRequest $request,
        #[CurrentUser] User $user,
        StoreCustomerAddressAction $action,
    ): JsonResponse {
        $address = $action->handle($user, $request->toDto());

        return (new CustomerAddressResource($address))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        CustomerAddress $address,
        #[CurrentUser] User $user,
        UpdateCustomerAddressAction $action,
    ): CustomerAddressResource {
        abort_unless($address->user_id === $user->id, 403);

        $action->handle($address, $request->toDto());

        return new CustomerAddressResource($address->refresh());
    }

    public function destroy(CustomerAddress $address, #[CurrentUser] User $user): JsonResponse
    {
        abort_unless($address->user_id === $user->id, 403);

        $address->delete();

        return response()->json(['message' => __('Address deleted.')]);
    }
}
