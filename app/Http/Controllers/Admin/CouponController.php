<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreCouponAction;
use App\Actions\UpdateCouponAction;
use App\Http\Requests\Admin\IndexCouponRequest;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use App\Queries\CouponListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CouponController
{
    public function index(IndexCouponRequest $request, CouponListQuery $query): Response
    {
        return Inertia::render('admin/coupons/list', [
            'coupons' => $query->execute($request->query(), $request->safe()->integer('per_page', 15)),
            'filters' => Inertia::always([
                'query' => $request->validated('query'),
                'is_active' => $request->safe()->has('is_active') ? $request->safe()->boolean('is_active') : null,
                'sort' => $request->validated('sort', 'created_at'),
                'direction' => $request->validated('direction', 'desc'),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/coupons/create');
    }

    public function store(StoreCouponRequest $request, StoreCouponAction $action): RedirectResponse
    {
        $coupon = $action->handle($request->toDto());

        if ($request->safe()->boolean('add_more')) {
            return back();
        }

        return to_route('admin.coupons.edit', $coupon);
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('admin/coupons/edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, UpdateCouponAction $action): RedirectResponse
    {
        $action->handle($coupon, $request->toDto());

        return back();
    }
}
