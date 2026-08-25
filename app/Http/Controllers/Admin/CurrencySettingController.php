<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Http\Requests\Admin\UpdateCurrencySettingRequest;
use App\Models\Setting;
use App\Queries\CurrencyListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CurrencySettingController
{
    public function show(CurrencyListQuery $currencyListQuery): Response
    {
        return Inertia::render('admin/settings/currency', [
            'settings' => fn (): Collection => Setting::getByGroup(SettingGroup::Currency),
            'currencies' => $currencyListQuery->execute(...),
            'filters' => Inertia::always([
                'query' => null,
                'sort' => 'code',
                'direction' => 'asc',
            ]),
        ]);
    }

    public function update(UpdateCurrencySettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
