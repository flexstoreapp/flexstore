<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CategoryResource;
use App\Queries\StorefrontCategoryListQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryController
{
    public function index(StorefrontCategoryListQuery $query): AnonymousResourceCollection
    {
        return CategoryResource::collection($query->execute());
    }
}
