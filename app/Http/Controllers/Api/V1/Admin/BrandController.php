<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyBrandAction;
use App\Actions\StoreBrandAction;
use App\Actions\UpdateBrandAction;
use App\Http\Requests\Admin\IndexBrandRequest;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\Api\V1\Admin\BrandResource;
use App\Models\Brand;
use App\Queries\BrandListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class BrandController
{
    public function index(IndexBrandRequest $request, BrandListQuery $query): AnonymousResourceCollection
    {
        return BrandResource::collection(
            $query->execute($request->validated(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function store(StoreBrandRequest $request, StoreBrandAction $action): JsonResponse
    {
        $brand = $action->handle($request->toDto());

        return (new BrandResource($this->presentable($brand)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($this->presentable($brand));
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action): BrandResource
    {
        $action->handle($brand, $request->toDto());

        return new BrandResource($this->presentable($brand->refresh()));
    }

    public function destroy(Brand $brand, BulkDestroyBrandAction $action): Response
    {
        $action->handle([$brand->id]);

        return response()->noContent();
    }

    private function presentable(Brand $brand): Brand
    {
        return $brand->load('image')->loadCount('products');
    }
}
