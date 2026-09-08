<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\DestroyCustomerAddressAction;
use App\Actions\StoreCustomerAddressAction;
use App\Actions\UpdateCustomerAddressAction;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class CustomerAddressController
{
    public function index(User $customer, CustomerAddressListQuery $query): AnonymousResourceCollection
    {
        return CustomerAddressResource::collection($query->execute($customer));
    }

    public function store(
        StoreCustomerAddressRequest $request,
        User $customer,
        StoreCustomerAddressAction $action,
    ): JsonResponse {
        $address = $action->handle($customer, $request->toDto());

        return (new CustomerAddressResource($address))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        User $customer,
        CustomerAddress $address,
        UpdateCustomerAddressAction $action,
    ): CustomerAddressResource {
        abort_unless($address->user_id === $customer->id, Response::HTTP_FORBIDDEN);

        $action->handle($address, $request->toDto());

        return new CustomerAddressResource($address->refresh());
    }

    public function destroy(
        User $customer,
        CustomerAddress $address,
        DestroyCustomerAddressAction $action,
    ): JsonResponse {
        abort_unless($address->user_id === $customer->id, Response::HTTP_FORBIDDEN);

        $action->handle($address);

        return response()->json(['message' => __('Address deleted.')]);
    }
}
