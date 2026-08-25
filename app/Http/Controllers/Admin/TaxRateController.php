<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyTaxRateAction;
use App\Actions\StoreTaxRateAction;
use App\Actions\UpdateTaxRateAction;
use App\Http\Requests\Admin\StoreTaxRateRequest;
use App\Http\Requests\Admin\UpdateTaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;

final readonly class TaxRateController
{
    public function store(StoreTaxRateRequest $request, StoreTaxRateAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(TaxRate $taxRate, UpdateTaxRateRequest $request, UpdateTaxRateAction $action): RedirectResponse
    {
        $action->handle($taxRate, $request->toDto());

        return back();
    }

    public function destroy(TaxRate $taxRate, DestroyTaxRateAction $action): RedirectResponse
    {
        $action->handle($taxRate);

        return back();
    }
}
