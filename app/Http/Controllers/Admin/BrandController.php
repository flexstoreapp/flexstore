<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreBrandAction;
use App\Actions\UpdateBrandAction;
use App\Http\Requests\Admin\IndexBrandRequest;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Queries\BrandListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BrandController
{
    public function index(IndexBrandRequest $request, BrandListQuery $query): Response
    {
        return Inertia::render('admin/brands/list', [
            'brands' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'is_active' => $request->safe()->has('is_active') ? $request->safe()->boolean('is_active') : null,
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function store(StoreBrandRequest $request, StoreBrandAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action): RedirectResponse
    {
        $action->handle($brand, $request->toDto());

        return back();
    }
}
