<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreCustomerAction;
use App\Actions\UpdateCustomerAction;
use App\Http\Requests\Admin\IndexCustomerRequest;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use App\Queries\CustomerListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CustomerController
{
    public function index(IndexCustomerRequest $request, CustomerListQuery $query): Response
    {
        return Inertia::render('admin/customers/list', [
            'customers' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/customers/create');
    }

    public function store(StoreCustomerRequest $request, StoreCustomerAction $action): RedirectResponse
    {
        $user = $action->handle($request->toDto());

        if ($request->safe()->boolean('add_more')) {
            return back();
        }

        return to_route('admin.customers.edit', $user);
    }

    public function edit(User $customer, CustomerAddressListQuery $addressQuery): Response
    {
        return Inertia::render('admin/customers/edit', [
            'customer' => $customer,
            'addresses' => $addressQuery->execute($customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, User $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $action->handle($customer, $request->toDto());

        return back();
    }
}
