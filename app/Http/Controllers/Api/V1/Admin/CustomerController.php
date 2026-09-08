<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyCustomerAction;
use App\Actions\StoreCustomerAction;
use App\Actions\UpdateCustomerAction;
use App\Http\Requests\Admin\IndexCustomerRequest;
use App\Http\Requests\Api\V1\Admin\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\Admin\CustomerListResource;
use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use App\Queries\CustomerListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class CustomerController
{
    public function index(IndexCustomerRequest $request, CustomerListQuery $query): AnonymousResourceCollection
    {
        return CustomerListResource::collection(
            $query->execute($request->validated(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function show(User $customer, CustomerAddressListQuery $addressQuery): CustomerResource
    {
        return new CustomerResource($this->presentable($customer, $addressQuery));
    }

    public function store(
        StoreCustomerRequest $request,
        StoreCustomerAction $action,
        CustomerAddressListQuery $addressQuery,
    ): JsonResponse {
        $customer = $action->handle($request->toDto());

        return (new CustomerResource($this->presentable($customer, $addressQuery)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCustomerRequest $request,
        User $customer,
        UpdateCustomerAction $action,
        CustomerAddressListQuery $addressQuery,
    ): CustomerResource {
        $action->handle($customer, $request->toDto());

        return new CustomerResource($this->presentable($customer->refresh(), $addressQuery));
    }

    public function destroy(User $customer, BulkDestroyCustomerAction $action): JsonResponse
    {
        abort_if($action->handle([$customer->id]) === 0, Response::HTTP_NOT_FOUND);

        return response()->json(['message' => __('Customer deleted.')]);
    }

    private function presentable(User $customer, CustomerAddressListQuery $addressQuery): User
    {
        $customer->setRelation('addresses', $addressQuery->execute($customer));

        return $customer->loadCount('orders as order_count');
    }
}
