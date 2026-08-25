<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\IndexShippingRateRequest;
use App\Http\Requests\Admin\UpdateShippingSettingRequest;
use App\Models\Setting;
use App\Queries\ShippingCarrierListQuery;
use App\Queries\ShippingRateListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShippingSettingController
{
    public function show(
        IndexShippingRateRequest $request,
        ShippingCarrierListQuery $shippingCarrierListQuery,
        ShippingRateListQuery $shippingRateListQuery
    ): Response {
        return Inertia::render('admin/settings/shipping', [
            'settings' => Setting::getByGroup(SettingGroup::Shipping),
            'shippingCarriers' => $shippingCarrierListQuery->execute(...),
            'shippingRates' => fn (): LengthAwarePaginator => $shippingRateListQuery->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'packages' => [],
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'page' => $request->validated('page'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function update(UpdateShippingSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
