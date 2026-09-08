<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Api\V1\Admin\SearchTermRequest;
use App\Http\Resources\Api\V1\Admin\BrandOptionResource;
use App\Queries\BrandSearchQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class BrandSearchController
{
    public function __invoke(SearchTermRequest $request, BrandSearchQuery $query): AnonymousResourceCollection
    {
        return BrandOptionResource::collection($query->execute($request->validated()));
    }
}
