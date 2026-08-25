<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSettingsAction;
use App\Enums\SettingGroup;
use App\Enums\TaxCategory;
use App\Http\Requests\Admin\IndexTaxRateRequest;
use App\Http\Requests\Admin\UpdateTaxSettingRequest;
use App\Models\Setting;
use App\Queries\TaxRateListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TaxSettingController
{
    public function show(IndexTaxRateRequest $request, TaxRateListQuery $taxRateListQuery): Response
    {
        return Inertia::render('admin/settings/tax', [
            'settings' => fn (): Collection => Setting::getByGroup(SettingGroup::Tax),
            'taxRates' => fn (): LengthAwarePaginator => $taxRateListQuery->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'taxCategories' => TaxCategory::options(),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'page' => $request->validated('page'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function update(UpdateTaxSettingRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }
}
