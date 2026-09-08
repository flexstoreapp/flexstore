<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ReorderCategoryAction;
use App\Http\Requests\Admin\ReorderCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryTreeResource;
use App\Models\Category;
use App\Queries\CategoryTreeQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryReorderController
{
    public function update(
        ReorderCategoryRequest $request,
        Category $category,
        ReorderCategoryAction $action,
        CategoryTreeQuery $query,
    ): AnonymousResourceCollection {
        $parentId = $request->validated('parent_id');

        $action->handle(
            $category,
            $parentId !== null ? (int) $parentId : null,
            $request->safe()->integer('position'),
        );

        return CategoryTreeResource::collection($query->execute());
    }
}
