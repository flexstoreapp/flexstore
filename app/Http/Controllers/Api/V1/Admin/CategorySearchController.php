<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Api\V1\Admin\SearchTermRequest;
use App\Http\Resources\Api\V1\Admin\CategoryOptionResource;
use App\Queries\CategorySearchQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategorySearchController
{
    public function __invoke(SearchTermRequest $request, CategorySearchQuery $query): AnonymousResourceCollection
    {
        return CategoryOptionResource::collection($query->execute($request->validated()));
    }
}
