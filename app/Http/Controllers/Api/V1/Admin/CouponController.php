<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyCouponAction;
use App\Actions\StoreCouponAction;
use App\Actions\UpdateCouponAction;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Api\V1\Admin\IndexCouponRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCouponRequest;
use App\Http\Resources\Api\V1\Admin\CouponResource;
use App\Models\Coupon;
use App\Queries\CouponListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class CouponController
{
    public function index(IndexCouponRequest $request, CouponListQuery $query): AnonymousResourceCollection
    {
        return CouponResource::collection(
            $query->execute($request->toFilters(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function store(StoreCouponRequest $request, StoreCouponAction $action): JsonResponse
    {
        return (new CouponResource($action->handle($request->toDto())->refresh()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Coupon $coupon): CouponResource
    {
        return new CouponResource($coupon);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, UpdateCouponAction $action): CouponResource
    {
        $action->handle($coupon, $request->toDto());

        return new CouponResource($coupon->refresh());
    }

    public function destroy(Coupon $coupon, BulkDestroyCouponAction $action): Response
    {
        $action->handle([$coupon->id]);

        return response()->noContent();
    }
}
