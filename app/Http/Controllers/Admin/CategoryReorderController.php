<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderCategoryAction;
use App\Http\Requests\Admin\ReorderCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

final readonly class CategoryReorderController
{
    public function update(
        ReorderCategoryRequest $request,
        Category $category,
        ReorderCategoryAction $action
    ): RedirectResponse {
        $parentId = $request->validated('parent_id');
        $action->handle(
            $category,
            $parentId !== null ? (int) $parentId : null,
            $request->safe()->integer('position')
        );

        return back();
    }
}
