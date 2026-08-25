<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyShippingRateAction;
use App\Actions\StoreShippingRateAction;
use App\Actions\UpdateShippingRateAction;
use App\Http\Requests\Admin\StoreShippingRateRequest;
use App\Http\Requests\Admin\UpdateShippingRateRequest;
use App\Models\ShippingRate;
use Illuminate\Http\RedirectResponse;

final readonly class ShippingRateController
{
    public function store(StoreShippingRateRequest $request, StoreShippingRateAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(ShippingRate $shippingRate, UpdateShippingRateRequest $request, UpdateShippingRateAction $action): RedirectResponse
    {
        $action->handle($shippingRate, $request->toDto());

        return back();
    }

    public function destroy(ShippingRate $shippingRate, DestroyShippingRateAction $action): RedirectResponse
    {
        $action->handle($shippingRate);

        return back();
    }
}
