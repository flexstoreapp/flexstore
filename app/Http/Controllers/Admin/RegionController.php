<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreRegionAction;
use App\Actions\UpdateRegionAction;
use App\Http\Requests\Admin\IndexRegionRequest;
use App\Http\Requests\Admin\StoreRegionRequest;
use App\Http\Requests\Admin\UpdateRegionRequest;
use App\Models\Region;
use App\Queries\RegionListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RegionController
{
    public function index(IndexRegionRequest $request, RegionListQuery $query): Response
    {
        return Inertia::render('admin/regions/list', [
            'regions' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
                'page' => $request->validated('page'),
            ]),
        ]);
    }

    public function store(StoreRegionRequest $request, StoreRegionAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(UpdateRegionRequest $request, Region $region, UpdateRegionAction $action): RedirectResponse
    {
        $action->handle($region, $request->toDto());

        return back();
    }
}
