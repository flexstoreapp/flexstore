<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BrandResource;
use App\Queries\StorefrontBrandListQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class BrandController
{
    public function index(StorefrontBrandListQuery $query): AnonymousResourceCollection
    {
        return BrandResource::collection($query->execute());
    }
}
