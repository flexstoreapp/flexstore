<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyShippingCarrierAction;
use App\Actions\StoreShippingCarrierAction;
use App\Actions\UpdateShippingCarrierAction;
use App\Http\Requests\Admin\StoreShippingCarrierRequest;
use App\Http\Requests\Admin\UpdateShippingCarrierRequest;
use App\Models\ShippingCarrier;
use Illuminate\Http\RedirectResponse;

final readonly class ShippingCarrierController
{
    public function store(StoreShippingCarrierRequest $request, StoreShippingCarrierAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(ShippingCarrier $carrier, UpdateShippingCarrierRequest $request, UpdateShippingCarrierAction $action): RedirectResponse
    {
        $action->handle($carrier, $request->toDto());

        return back();
    }

    public function destroy(ShippingCarrier $carrier, DestroyShippingCarrierAction $action): RedirectResponse
    {
        $action->handle($carrier);

        return back();
    }
}
