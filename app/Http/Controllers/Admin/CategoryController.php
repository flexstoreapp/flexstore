<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyCategoryAction;
use App\Actions\StoreCategoryAction;
use App\Actions\UpdateCategoryAction;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Queries\CategoryTreeQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CategoryController
{
    public function index(CategoryTreeQuery $query): Response
    {
        return Inertia::render('admin/categories/list', [
            'categories' => $query->execute(),
        ]);
    }

    public function store(StoreCategoryRequest $request, StoreCategoryAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $action->handle($category, $request->toDto());

        return back();
    }

    public function destroy(Category $category, DestroyCategoryAction $action): RedirectResponse
    {
        $action->handle($category);

        return back();
    }
}
