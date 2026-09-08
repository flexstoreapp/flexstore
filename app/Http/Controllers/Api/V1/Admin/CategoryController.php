<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\DestroyCategoryAction;
use App\Actions\StoreCategoryAction;
use App\Actions\UpdateCategoryAction;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use App\Http\Resources\Api\V1\Admin\CategoryTreeResource;
use App\Models\Category;
use App\Queries\CategoryTreeQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class CategoryController
{
    public function index(CategoryTreeQuery $query): AnonymousResourceCollection
    {
        return CategoryTreeResource::collection($query->execute());
    }

    public function store(StoreCategoryRequest $request, StoreCategoryAction $action): JsonResponse
    {
        $category = $action->handle($request->toDto());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        UpdateCategoryAction $action,
    ): CategoryResource {
        $action->handle($category, $request->toDto());

        return new CategoryResource($category->refresh());
    }

    public function destroy(Category $category, DestroyCategoryAction $action): JsonResponse
    {
        $action->handle($category);

        return response()->json(['message' => __('Category deleted.')]);
    }
}
