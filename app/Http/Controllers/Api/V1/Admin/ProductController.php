<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\BulkDestroyProductAction;
use App\Actions\StoreProductAction;
use App\Actions\UpdateProductAction;
use App\Http\Requests\Admin\IndexAdminProductRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Api\V1\Admin\AdminProductListResource;
use App\Http\Resources\Api\V1\Admin\AdminProductResource;
use App\Models\Product;
use App\Queries\ProductListQuery;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class ProductController
{
    public function index(IndexAdminProductRequest $request, ProductListQuery $query): AnonymousResourceCollection
    {
        return AdminProductListResource::collection(
            $query->execute($request->validated(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function store(StoreProductRequest $request, StoreProductAction $action): JsonResponse
    {
        $product = $action->handle($request->toDto());

        return (new AdminProductResource($this->withDetails($product)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): AdminProductResource
    {
        return new AdminProductResource($this->withDetails($product));
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): AdminProductResource
    {
        $action->handle($product, $request->toDto());

        return new AdminProductResource($this->withDetails($product->refresh()));
    }

    public function destroy(Product $product, BulkDestroyProductAction $action): Response
    {
        $action->handle([$product->id]);

        return response()->noContent();
    }

    private function withDetails(Product $product): Product
    {
        return $product->load([
            ...AdminProductResource::RELATIONS,
            'variants' => fn (Relation $query): Relation => $query->latest(),
        ]);
    }
}
